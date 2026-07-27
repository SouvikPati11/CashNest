<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Wallet transaction (ledger) repository contract.
 *
 * Append-only data access for `wallet_transactions`. No update/delete methods
 * exist by design — the ledger is immutable.
 */
interface WalletTransactionRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findByUuid(string $uuid): ?array;

    /**
     * Idempotency lookup by the unique reference id.
     *
     * @return array<string, mixed>|null
     */
    public function findByReferenceId(string $referenceId): ?array;

    /**
     * Fetch a transaction scoped to its owner (ownership enforcement).
     *
     * @return array<string, mixed>|null
     */
    public function findByUuidForUser(string $uuid, int $userId): ?array;

    /**
     * Insert a ledger row and return its id.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * Query a user's ledger with filtering, ordering, and cursor/offset paging.
     *
     * @param array<string, mixed> $filters  Allowed keys: type, direction, date_from, date_to.
     * @param 'id'|'amount'        $orderColumn
     * @param 'ASC'|'DESC'         $orderDir
     * @return array<int, array<string, mixed>>
     */
    public function queryForUser(
        int $userId,
        array $filters,
        string $orderColumn,
        string $orderDir,
        int $limit,
        ?int $beforeId,
        int $offset
    ): array;
}
