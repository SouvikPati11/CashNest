<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\NotificationCampaignRepositoryInterface;
use App\Contracts\NotificationCampaignServiceInterface;
use App\Contracts\NotificationServiceInterface;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Models\NotificationCampaign;
use Core\Contracts\LoggerInterface;

/**
 * Notification campaign service (broadcast fan-out).
 *
 * Campaigns are created in draft and later dispatched: the campaign is fanned
 * out into per-user inbox rows in chunks, each of which queues a push through
 * the notification service (never an inline FCM call). Delivery stats are
 * updated as the fan-out proceeds. No admin HTTP surface is built here.
 */
final class NotificationCampaignService implements NotificationCampaignServiceInterface
{
    /** Recipients are processed in chunks (shared-hosting / queue friendly). */
    private const CHUNK_SIZE = 500;

    public function __construct(
        private NotificationCampaignRepositoryInterface $campaigns,
        private NotificationServiceInterface $notifications,
        private LoggerInterface $logger
    ) {
    }

    public function create(array $attributes): NotificationCampaign
    {
        $type = is_string($attributes['type'] ?? null) ? $attributes['type'] : NotificationCampaign::TYPE_PROMOTIONAL;

        $id = (int) $this->campaigns->create([
            'title'           => (string) ($attributes['title'] ?? ''),
            'body'            => (string) ($attributes['body'] ?? ''),
            'type'            => $type,
            'audience'        => is_string($attributes['audience'] ?? null)
                ? $attributes['audience']
                : NotificationCampaign::AUDIENCE_ALL,
            'audience_filter' => $this->encode($attributes['audience_filter'] ?? null),
            'deep_link'       => $attributes['deep_link'] ?? null,
            'image_url'       => $attributes['image_url'] ?? null,
            'scheduled_at'    => $attributes['scheduled_at'] ?? null,
            'status'          => NotificationCampaign::STATUS_DRAFT,
            'created_by'      => isset($attributes['created_by']) ? (int) $attributes['created_by'] : null,
        ]);

        return $this->reload($id);
    }

    public function dispatch(int $campaignId, array $userIds): NotificationCampaign
    {
        $row = $this->campaigns->find($campaignId);
        if ($row === null) {
            throw new NotFoundException('Campaign not found.');
        }

        $campaign     = NotificationCampaign::fromRow($row);
        $dispatchable = [NotificationCampaign::STATUS_DRAFT, NotificationCampaign::STATUS_SCHEDULED];

        if (!in_array($campaign->status(), $dispatchable, true)) {
            throw new HttpException(409, 'RESOURCE_CONFLICT', 'This campaign can no longer be dispatched.');
        }

        $userIds = array_values(array_unique(array_map('intval', $userIds)));

        $this->campaigns->update($campaignId, [
            'status'         => NotificationCampaign::STATUS_SENDING,
            'total_targeted' => count($userIds),
        ]);

        $sent   = 0;
        $failed = 0;

        foreach (array_chunk($userIds, self::CHUNK_SIZE) as $chunk) {
            foreach ($chunk as $userId) {
                try {
                    $this->notifications->send($userId, [
                        'campaign_id' => $campaignId,
                        'title'       => $campaign->title(),
                        'body'        => $campaign->body(),
                        'type'        => $campaign->type(),
                        'deep_link'   => $row['deep_link'] ?? null,
                        'image_url'   => $row['image_url'] ?? null,
                    ]);
                    $sent++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->logger->error('Campaign fan-out failed for user.', [
                        'campaign_id' => $campaignId,
                        'user_id'     => $userId,
                        'error'       => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->campaigns->update($campaignId, [
            'status'       => NotificationCampaign::STATUS_SENT,
            'total_sent'   => $sent,
            'total_failed' => $failed,
        ]);

        $this->logger->info('Campaign dispatched.', [
            'campaign_id' => $campaignId,
            'sent'        => $sent,
            'failed'      => $failed,
        ]);

        return $this->reload($campaignId);
    }

    private function reload(int $campaignId): NotificationCampaign
    {
        $row = $this->campaigns->find($campaignId);

        return NotificationCampaign::fromRow($row ?? []);
    }

    private function encode(mixed $value): ?string
    {
        if (!is_array($value) || $value === []) {
            return null;
        }

        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? null : $json;
    }
}
