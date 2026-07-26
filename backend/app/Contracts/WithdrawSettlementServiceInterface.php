<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\WithdrawRequest;

/**
 * Withdraw settlement service contract (admin-review-ready domain operations).
 *
 * Drives the approval/payment side of the withdrawal state machine. No admin
 * HTTP surface is built here — these are the transactional domain operations an
 * admin panel (or an automated processor) will call:
 *
 *   pending  → approved      approve()
 *   approved → paid          markPaid()  (release hold, then debit payout)
 *   pending  → rejected      reject()    (release hold)
 *
 * Every coin movement flows through LedgerService reserve()/release()/debit();
 * balances are never mutated directly.
 */
interface WithdrawSettlementServiceInterface
{
    /**
     * Move a pending request to approved (no ledger movement; hold stands).
     *
     * @throws \App\Exceptions\HttpException On an invalid transition.
     * @throws \App\Exceptions\NotFoundException
     */
    public function approve(int $requestId, ?int $adminId = null, ?string $note = null): WithdrawRequest;

    /**
     * Settle an approved request: release the hold then debit the payout.
     *
     * @throws \App\Exceptions\HttpException On an invalid transition.
     * @throws \App\Exceptions\NotFoundException
     */
    public function markPaid(
        int $requestId,
        ?int $adminId = null,
        ?string $externalReference = null,
        ?string $note = null
    ): WithdrawRequest;

    /**
     * Reject a pending request and release the hold.
     *
     * @throws \App\Exceptions\HttpException On an invalid transition.
     * @throws \App\Exceptions\NotFoundException
     */
    public function reject(int $requestId, ?int $adminId = null, ?string $note = null): WithdrawRequest;
}
