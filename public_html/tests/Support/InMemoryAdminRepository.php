<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\AdminRepositoryInterface;

/**
 * In-memory admin repository for DB-free auth tests.
 */
final class InMemoryAdminRepository implements AdminRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array<int, array{ip: string, at: string}> */
    public array $logins = [];

    private int $nextId = 1;

    /**
     * @param array<string, mixed> $data
     */
    public function seed(array $data): int
    {
        $id = $this->nextId++;

        $this->rows[$id] = array_merge([
            'id'             => $id,
            'role_id'        => 1,
            'name'           => 'Admin',
            'status'         => 'active',
            'two_fa_enabled' => 0,
            'deleted_at'     => null,
        ], $data, ['id' => $id]);

        return $id;
    }

    public function findActiveByEmail(string $email): ?array
    {
        foreach ($this->rows as $row) {
            if (
                ($row['email'] ?? null) === $email
                && ($row['status'] ?? null) === 'active'
                && ($row['deleted_at'] ?? null) === null
            ) {
                return $row;
            }
        }

        return null;
    }

    public function find(int $id): ?array
    {
        return $this->rows[$id] ?? null;
    }

    public function recordLogin(int $id, string $ip, string $at): void
    {
        $this->logins[$id] = ['ip' => $ip, 'at' => $at];
    }
}
