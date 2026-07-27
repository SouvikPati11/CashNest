<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\SessionRepositoryInterface;

/**
 * In-memory session repository for DB-free token/session tests.
 */
final class InMemorySessionRepository implements SessionRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    public function find(int|string $id): ?array
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function findByRefreshHash(string $hash): ?array
    {
        foreach ($this->rows as $row) {
            if (($row['refresh_token_hash'] ?? null) === $hash) {
                return $row;
            }
        }

        return null;
    }

    public function create(array $data): string
    {
        $id = $this->nextId++;
        $this->rows[$id] = $data + ['id' => $id, 'revoked_at' => $data['revoked_at'] ?? null];

        return (string) $id;
    }

    public function revoke(int $id, string $timestamp): int
    {
        if (!isset($this->rows[$id])) {
            return 0;
        }

        $this->rows[$id]['revoked_at'] = $timestamp;

        return 1;
    }

    public function revokeAllForUser(int $userId, string $timestamp): int
    {
        $count = 0;

        foreach ($this->rows as $id => $row) {
            if (($row['user_id'] ?? null) === $userId && ($row['revoked_at'] ?? null) === null) {
                $this->rows[$id]['revoked_at'] = $timestamp;
                $count++;
            }
        }

        return $count;
    }
}
