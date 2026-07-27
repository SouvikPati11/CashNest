<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Notification campaign repository contract — owns `notification_campaigns`.
 */
interface NotificationCampaignRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int|string $id, array $data): int;
}
