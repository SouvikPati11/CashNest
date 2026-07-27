<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * User settings repository contract — owns `user_settings`.
 */
interface UserSettingsRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findByUserId(int $userId): ?array;

    /**
     * Create-or-update a user's settings columns.
     *
     * @param array<string, mixed> $data
     */
    public function upsert(int $userId, array $data): void;
}
