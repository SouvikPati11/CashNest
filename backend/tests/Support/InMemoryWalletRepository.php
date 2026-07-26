<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\WalletRepositoryInterface;

/**
 * In-memory wallet repository for DB-free ledger tests. lockByUserId simply
 * returns the row (no real lock is needed in single-threaded tests).
 */
final class InMemoryWalletRepository implements WalletRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    public function find(int|string $id): ?array
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function findByUserId(int $userId): ?array
    {
        foreach ($this->rows as $row) {
            if (($row['user_id'] ?? null) === $userId) {
                return $row;
            }
        }

        return null;
    }

    public function lockByUserId(int $userId): ?array
    {
        return $this->findByUserId($userId);
    }

    public function create(array $data): string
    {
        $id = $this->nextId++;

        $this->rows[$id] = array_merge([
            'id'                    => $id,
            'coin_balance'          => 0,
            'coin_reserved'         => 0,
            'lifetime_coins_earned' => 0,
            'lifetime_coins_spent'  => 0,
            'cash_balance'          => '0.0000',
            'cash_reserved'         => '0.0000',
            'version'               => 0,
            'last_transaction_id'   => null,
        ], $data, ['id' => $id]);

        return (string) $id;
    }

    public function applyBalances(int $id, array $data): int
    {
        if (!isset($this->rows[$id])) {
            return 0;
        }

        $this->rows[$id] = array_merge($this->rows[$id], $data);

        return 1;
    }
}
