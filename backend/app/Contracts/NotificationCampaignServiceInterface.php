<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\NotificationCampaign;

/**
 * Notification campaign service contract (broadcast fan-out).
 *
 * Campaigns are admin-composed and dispatched here (no admin HTTP surface is
 * built). Dispatch fans a campaign out into per-user `notifications` rows in
 * chunks and queues each push, updating the campaign's delivery stats.
 */
interface NotificationCampaignServiceInterface
{
    /**
     * Create a campaign in draft state.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): NotificationCampaign;

    /**
     * Fan a campaign out to the given recipient user ids, creating one inbox
     * row per user and queuing each push. Returns the campaign with updated
     * delivery stats.
     *
     * @param array<int, int> $userIds
     *
     * @throws \App\Exceptions\HttpException On an invalid campaign state.
     * @throws \App\Exceptions\NotFoundException
     */
    public function dispatch(int $campaignId, array $userIds): NotificationCampaign;
}
