<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\NotificationPreferenceRepositoryInterface;

/**
 * In-memory notification preference repository for DB-free service tests.
 * Mirrors the lazy-upsert semantics of the `user_settings`-backed repository.
 */
final class InMemoryNotificationPreferenceRepository implements NotificationPreferenceRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function findByUserId(int $userId): ?array
    {
        return $this->rows[$userId] ?? null;
    }

    public function upsertToggles(int $userId, array $toggles): void
    {
        $existing = $this->rows[$userId] ?? ['user_id' => $userId];

        $this->rows[$userId] = array_merge($existing, $toggles);
    }
}
