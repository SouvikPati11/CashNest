<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\UserRepositoryInterface;

/**
 * In-memory user repository for fast, DB-free service unit tests.
 */
final class InMemoryUserRepository implements UserRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    public function find(int|string $id): ?array
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->first('uuid', $uuid);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->first('email', $email);
    }

    public function findByReferralCode(string $code): ?array
    {
        return $this->first('referral_code', $code);
    }

    public function existsByEmail(string $email): bool
    {
        return $this->first('email', $email) !== null;
    }

    public function existsByReferralCode(string $code): bool
    {
        return $this->first('referral_code', $code) !== null;
    }

    public function create(array $data): string
    {
        $id = $this->nextId++;
        $this->rows[$id] = $data + ['id' => $id];

        return (string) $id;
    }

    public function update(int|string $id, array $data): int
    {
        $id = (int) $id;

        if (!isset($this->rows[$id])) {
            return 0;
        }

        $this->rows[$id] = array_merge($this->rows[$id], $data);

        return 1;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function first(string $column, mixed $value): ?array
    {
        foreach ($this->rows as $row) {
            if (($row[$column] ?? null) === $value) {
                return $row;
            }
        }

        return null;
    }
}
