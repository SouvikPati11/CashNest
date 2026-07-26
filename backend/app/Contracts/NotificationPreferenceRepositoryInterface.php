<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Notification preference repository contract.
 *
 * Reads and writes ONLY the notification toggle columns of `user_settings`
 * (push master, transactional, promotional). The full Settings module is out of
 * scope; a row is created lazily on first write.
 */
interface NotificationPreferenceRepositoryInterface
{
    /**
     * The user's settings row, if one exists.
     *
     * @return array<string, mixed>|null
     */
    public function findByUserId(int $userId): ?array;

    /**
     * Upsert the notification toggle columns for a user.
     *
     * @param array<string, int> $toggles Column => 0|1.
     */
    public function upsertToggles(int $userId, array $toggles): void;
}
