<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LedgerServiceInterface;
use App\Contracts\WithdrawRequestRepositoryInterface;
use App\Contracts\WithdrawSettlementServiceInterface;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Models\WithdrawRequest;
use Core\Contracts\LoggerInterface;

/**
 * Withdraw settlement service — the approval/payment side of the state machine.
 *
 * These are transactional domain operations, ready for an admin panel or an
 * automated processor to call (no admin HTTP surface is built here). Settlement
 * of an approved request is expressed purely with the ledger primitives the task
 * mandates: the held coins are released back to the spendable balance and then
 * debited out as the payout, leaving `coin_reserved` reduced by the amount and
 * a `withdrawal_debit` ledger row recorded. Rejection releases the hold. Every
 * operation is idempotent by ledger `reference_id`.
 */
final class WithdrawSettlementService implements WithdrawSettlementServiceInterface
{
    private const SOURCE_MODULE = 'withdraw';

    /** Ledger type for the payout debit (a member of WalletTransaction::TYPES). */
    private const TYPE_WITHDRAWAL_DEBIT = 'withdrawal_debit';

    public function __construct(
        private WithdrawRequestRepositoryInterface $requests,
        private LedgerServiceInterface $ledger,
        private LoggerInterface $logger
    ) {
    }

    public function approve(int $requestId, ?int $adminId = null, ?string $note = null): WithdrawRequest
    {
        $request = $this->load($requestId);

        if ($request->status() === WithdrawRequest::STATUS_APPROVED) {
            return $request;
        }

        $this->assertTransition($request, WithdrawRequest::STATUS_APPROVED);

        $this->requests->updateRequest($requestId, array_filter([
            'status'     => WithdrawRequest::STATUS_APPROVED,
            'admin_id'   => $adminId,
            'admin_note' => $note,
        ], static fn(mixed $v): bool => $v !== null));

        $this->recordHistory($request, WithdrawRequest::STATUS_APPROVED, $adminId, $note ?? 'Approved.');
        $this->logger->info('Withdrawal approved.', ['request_id' => $requestId, 'admin_id' => $adminId]);

        return $this->reload($requestId);
    }

    public function markPaid(
        int $requestId,
        ?int $adminId = null,
        ?string $externalReference = null,
        ?string $note = null
    ): WithdrawRequest {
        $request = $this->load($requestId);

        if ($request->status() === WithdrawRequest::STATUS_PAID) {
            return $request;
        }

        $this->assertTransition($request, WithdrawRequest::STATUS_PAID);

        $uuid  = (string) $request->uuid();
        $coins = $request->coinsAmount();

        // Settle held coins using only the mandated primitives: release the hold
        // back to spendable, then debit the payout out of the balance.
        $this->ledger->release(
            $request->userId(),
            $coins,
            self::SOURCE_MODULE,
            'withdraw_settle:' . $uuid,
            ['description' => 'Withdrawal settlement release', 'source_id' => $requestId]
        );

        $debit = $this->ledger->debit(
            $request->userId(),
            $coins,
            self::TYPE_WITHDRAWAL_DEBIT,
            self::SOURCE_MODULE,
            'withdraw_debit:' . $uuid,
            ['description' => 'Withdrawal payout', 'source_id' => $requestId]
        );

        $this->requests->updateRequest($requestId, array_filter([
            'status'               => WithdrawRequest::STATUS_PAID,
            'debit_transaction_id' => (int) $debit->id(),
            'admin_id'             => $adminId,
            'admin_note'           => $note,
            'external_reference'   => $externalReference,
            'processed_at'         => gmdate('Y-m-d H:i:s'),
        ], static fn(mixed $v): bool => $v !== null));

        $this->recordHistory($request, WithdrawRequest::STATUS_PAID, $adminId, $note ?? 'Paid.');
        $this->logger->info('Withdrawal paid.', ['request_id' => $requestId, 'coins' => $coins]);

        return $this->reload($requestId);
    }

    public function reject(int $requestId, ?int $adminId = null, ?string $note = null): WithdrawRequest
    {
        $request = $this->load($requestId);

        if ($request->status() === WithdrawRequest::STATUS_REJECTED) {
            return $request;
        }

        $this->assertTransition($request, WithdrawRequest::STATUS_REJECTED);

        $refund = $this->ledger->release(
            $request->userId(),
            $request->coinsAmount(),
            self::SOURCE_MODULE,
            'withdraw_release:' . (string) $request->uuid(),
            ['description' => 'Withdrawal rejected', 'source_id' => $requestId]
        );

        $this->requests->updateRequest($requestId, array_filter([
            'status'                => WithdrawRequest::STATUS_REJECTED,
            'refund_transaction_id' => (int) $refund->id(),
            'admin_id'              => $adminId,
            'admin_note'            => $note,
            'processed_at'          => gmdate('Y-m-d H:i:s'),
        ], static fn(mixed $v): bool => $v !== null));

        $this->recordHistory($request, WithdrawRequest::STATUS_REJECTED, $adminId, $note ?? 'Rejected.');
        $this->logger->info('Withdrawal rejected.', ['request_id' => $requestId, 'admin_id' => $adminId]);

        return $this->reload($requestId);
    }

    private function load(int $requestId): WithdrawRequest
    {
        $row = $this->requests->find($requestId);

        if ($row === null) {
            throw new NotFoundException('Withdrawal request not found.');
        }

        return WithdrawRequest::fromRow($row);
    }

    private function reload(int $requestId): WithdrawRequest
    {
        $row = $this->requests->find($requestId);

        return WithdrawRequest::fromRow($row ?? []);
    }

    private function assertTransition(WithdrawRequest $request, string $to): void
    {
        if (!$request->canTransitionTo($to)) {
            throw new HttpException(
                409,
                'RESOURCE_CONFLICT',
                sprintf('Cannot move a "%s" request to "%s".', $request->status(), $to)
            );
        }
    }

    private function recordHistory(WithdrawRequest $request, string $to, ?int $adminId, string $note): void
    {
        $this->requests->addHistory(array_filter([
            'withdraw_request_id' => (int) $request->id(),
            'from_status'         => $request->status(),
            'to_status'           => $to,
            'changed_by_admin_id' => $adminId,
            'note'                => $note,
        ], static fn(mixed $v): bool => $v !== null));
    }
}
