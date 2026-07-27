<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Withdraw request repository contract.
 *
 * Owns `withdraw_requests` and its append-only `withdraw_history` audit trail.
 * Balance movements are NEVER performed here — they go through LedgerService.
 */
interface WithdrawRequestRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findByUuid(string $uuid): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findByUuidForUser(string $uuid, int $userId): ?array;

    /**
     * Find the request whose hold ledger row has the given id (idempotency).
     *
     * @return array<string, mixed>|null
     */
    public function findByHoldTransactionId(int $holdTransactionId): ?array;

    /**
     * A page of a user's requests, newest first (fetch limit+1 to detect more).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(int $userId, int $limit, int $offset, ?string $status): array;

    /**
     * @param array<string, mixed> $data
     */
    public function updateRequest(int $id, array $data): int;

    /**
     * Append a status-transition audit row to `withdraw_history`.
     *
     * @param array<string, mixed> $data
     */
    public function addHistory(array $data): string;

    /**
     * Ordered status timeline for a request (oldest first).
     *
     * @return array<int, array<string, mixed>>
     */
    public function historyForRequest(int $requestId): array;
}
