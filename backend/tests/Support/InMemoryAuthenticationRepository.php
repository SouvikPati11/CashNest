<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\AuthenticationRepositoryInterface;
use App\Models\AuthProvider;

/**
 * In-memory authentication repository for DB-free service unit tests.
 */
final class InMemoryAuthenticationRepository implements AuthenticationRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    public function find(int|string $id): ?array
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function findByProviderUid(string $provider, string $providerUid): ?array
    {
        foreach ($this->rows as $row) {
            if (($row['provider'] ?? null) === $provider && ($row['provider_uid'] ?? null) === $providerUid) {
                return $row;
            }
        }

        return null;
    }

    public function findByUserAndProvider(int $userId, string $provider): ?array
    {
        foreach ($this->rows as $row) {
            if (($row['user_id'] ?? null) === $userId && ($row['provider'] ?? null) === $provider) {
                return $row;
            }
        }

        return null;
    }

    public function findEmailProviderByEmail(string $email): ?array
    {
        foreach ($this->rows as $row) {
            if (($row['provider'] ?? null) === AuthProvider::PROVIDER_EMAIL && ($row['email'] ?? null) === $email) {
                return $row;
            }
        }

        return null;
    }

    public function create(array $data): string
    {
        $id = $this->nextId++;
        $this->rows[$id] = $data + ['id' => $id];

        return (string) $id;
    }

    public function markUsed(int $id, string $timestamp): int
    {
        if (!isset($this->rows[$id])) {
            return 0;
        }

        $this->rows[$id]['last_used_at'] = $timestamp;

        return 1;
    }
}
