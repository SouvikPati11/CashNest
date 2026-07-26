<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\UserSettingsRepositoryInterface;

/**
 * In-memory user settings repository for DB-free service tests.
 */
final class InMemoryUserSettingsRepository implements UserSettingsRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function findByUserId(int $userId): ?array
    {
        return $this->rows[$userId] ?? null;
    }

    public function upsert(int $userId, array $data): void
    {
        $existing = $this->rows[$userId] ?? ['user_id' => $userId];

        $this->rows[$userId] = array_merge($existing, $data);
    }
}
