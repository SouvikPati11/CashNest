<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\NotificationRepositoryInterface;
use App\Contracts\NotificationServiceInterface;
use App\Exceptions\NotFoundException;
use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use App\Resources\NotificationResource;
use App\Services\Notification\NotificationTemplates;
use Core\Contracts\LoggerInterface;
use Core\Contracts\QueueInterface;

/**
 * Notification service (user-facing inbox + queued dispatch).
 *
 * Creating a notification writes the inbox row (`push_status = queued`) and
 * ENQUEUES a push job — there is never an inline FCM call. The queue worker
 * later resolves preferences and device tokens and dispatches via the Firebase
 * dispatcher. Read operations serve the inbox, badge count, and read state.
 */
final class NotificationService implements NotificationServiceInterface
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT     = 100;

    public function __construct(
        private NotificationRepositoryInterface $notifications,
        private QueueInterface $queue,
        private NotificationTemplates $templates,
        private LoggerInterface $logger
    ) {
    }

    public function send(int $userId, array $attributes): Notification
    {
        $type = is_string($attributes['type'] ?? null) && in_array($attributes['type'], Notification::TYPES, true)
            ? $attributes['type']
            : Notification::TYPE_SYSTEM;

        $id = (int) $this->notifications->create([
            'user_id'     => $userId,
            'campaign_id' => isset($attributes['campaign_id']) ? (int) $attributes['campaign_id'] : null,
            'title'       => $this->str($attributes['title'] ?? '', 160),
            'body'        => $this->str($attributes['body'] ?? '', 1000),
            'type'        => $type,
            'deep_link'   => $this->nullableStr($attributes['deep_link'] ?? null, 512),
            'image_url'   => $this->nullableStr($attributes['image_url'] ?? null, 512),
            'data'        => $this->encodeData($attributes['data'] ?? null),
            'is_read'     => 0,
            'push_status' => Notification::PUSH_QUEUED,
        ]);

        // All notification events are queued; the worker performs the dispatch.
        $this->queue->push(SendPushNotificationJob::NAME, ['notification_id' => $id]);

        $this->logger->info('Notification created and push queued.', ['user_id' => $userId, 'id' => $id]);

        $row = $this->notifications->find($id);

        return Notification::fromRow($row ?? []);
    }

    public function sendTemplate(int $userId, string $templateKey, array $params = []): Notification
    {
        $attributes = $this->templates->render($templateKey, $params);

        if (isset($params['data']) && is_array($params['data'])) {
            $attributes['data'] = $params['data'];
        }

        return $this->send($userId, $attributes);
    }

    public function inbox(int $userId, array $params): array
    {
        $limit  = $this->resolveLimit($params['limit'] ?? null);
        $page   = max(1, (int) ($params['page'] ?? 1));
        $type   = is_string($params['type'] ?? null) && $params['type'] !== '' ? $params['type'] : null;
        $isRead = $this->resolveBool($params['is_read'] ?? null);

        $rows    = $this->notifications->listForUser($userId, $limit + 1, ($page - 1) * $limit, $type, $isRead);
        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        $items = array_map(
            static fn(array $row): array => NotificationResource::toArray(Notification::fromRow($row)),
            $rows
        );

        return [
            'items'    => $items,
            'has_more' => $hasMore,
            'page'     => $page,
            'limit'    => $limit,
        ];
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->unreadCount($userId);
    }

    public function markRead(int $userId, int $id): array
    {
        $row = $this->notifications->findForUser($id, $userId);
        if ($row === null) {
            throw new NotFoundException('Notification not found.');
        }

        $this->notifications->markRead($id, $userId, gmdate('Y-m-d H:i:s'));

        return ['id' => $id, 'is_read' => true];
    }

    public function markAllRead(int $userId): int
    {
        return $this->notifications->markAllRead($userId, gmdate('Y-m-d H:i:s'));
    }

    private function resolveLimit(mixed $limit): int
    {
        $value = is_numeric($limit) ? (int) $limit : self::DEFAULT_LIMIT;

        return max(1, min($value, self::MAX_LIMIT));
    }

    private function resolveBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    private function str(mixed $value, int $max): string
    {
        return mb_substr(is_scalar($value) ? (string) $value : '', 0, $max);
    }

    private function nullableStr(mixed $value, int $max): ?string
    {
        if (!is_scalar($value) || (string) $value === '') {
            return null;
        }

        return mb_substr((string) $value, 0, $max);
    }

    /**
     * @param mixed $data
     */
    private function encodeData(mixed $data): ?string
    {
        if (!is_array($data) || $data === []) {
            return null;
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? null : $json;
    }
}
