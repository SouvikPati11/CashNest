<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Withdraw service contract (user-facing).
 *
 * Serves payout methods, creates payout requests (reserving coins through the
 * ledger), lists a user's history, exposes a single request with its status
 * timeline, and cancels a pending request (releasing the hold).
 */
interface WithdrawServiceInterface
{
    /**
     * Active payout methods with limits/fees.
     *
     * @return array<int, array<string, mixed>>
     */
    public function methods(): array;

    /**
     * Create a withdrawal request; reserves balance via the ledger.
     *
     * @param array<string, mixed> $paymentDetail
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\HttpException
     * @throws \App\Exceptions\ValidationException
     */
    public function request(
        int $userId,
        string $methodCode,
        int $coinsAmount,
        array $paymentDetail,
        string $idempotencyKey,
        ?string $ip
    ): array;

    /**
     * A page of the user's withdrawal history.
     *
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    public function history(int $userId, array $params): array;

    /**
     * A single request owned by the user, with its status timeline.
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function detail(int $userId, string $uuid): array;

    /**
     * Cancel a pending request and release the hold.
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\HttpException
     * @throws \App\Exceptions\NotFoundException
     */
    public function cancel(int $userId, string $uuid): array;
}
