<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\NotificationCampaignRepositoryInterface;

/**
 * Notification campaign repository — owns `notification_campaigns`.
 */
final class NotificationCampaignRepository extends BaseRepository implements NotificationCampaignRepositoryInterface
{
    protected string $table = 'notification_campaigns';
}
