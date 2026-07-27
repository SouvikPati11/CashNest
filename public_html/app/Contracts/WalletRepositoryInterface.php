<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Wallet repository contract.
 *
 * Data access for `wallets`, including the row-lock primitive the ledger uses to
 * serialise balance mutations. `applyBalances` is reserved for the LedgerService
 * and must only ever be called in the same transaction that inserts a ledger row.
 */
interface WalletRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findByUserId(int $userId): ?array;

    /**
     * Fetch a wallet row with a `FOR UPDATE` lock. MUST be called inside an
     * active transaction; the lock serialises concurrent mutations per user.
     *
     * @return array<string, mixed>|null
     */
    public function lockByUserId(int $userId): ?array;

    /**
     * Create a wallet row and return its id.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * Apply new balance columns (ledger-driven only); returns affected rows.
     *
     * @param array<string, mixed> $data
     */
    public function applyBalances(int $id, array $data): int;
}
