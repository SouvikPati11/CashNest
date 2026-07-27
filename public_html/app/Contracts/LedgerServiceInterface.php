<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\WalletTransaction;

/**
 * Ledger service contract — the single writer of money movements.
 *
 * Every operation runs in a database transaction, locks the wallet row, is
 * idempotent by `reference_id`, and always writes exactly one immutable ledger
 * entry alongside the balance update. This is the ONLY way balances change.
 *
 * Shared option keys: description (string), metadata (array), source_id (int),
 * cash_amount (string), related_transaction_id (int), performed_by_admin_id (int).
 */
interface LedgerServiceInterface
{
    /**
     * Credit coins to a user's spendable balance.
     *
     * @param array<string, mixed> $options
     *
     * @throws \App\Exceptions\HttpException On invalid input.
     */
    public function credit(
        int $userId,
        int $amount,
        string $type,
        string $sourceModule,
        string $referenceId,
        array $options = []
    ): WalletTransaction;

    /**
     * Debit coins from a user's spendable balance.
     *
     * @param array<string, mixed> $options
     *
     * @throws \App\Exceptions\HttpException On insufficient balance or invalid input.
     */
    public function debit(
        int $userId,
        int $amount,
        string $type,
        string $sourceModule,
        string $referenceId,
        array $options = []
    ): WalletTransaction;

    /**
     * Reserve (hold) coins: move them from spendable to reserved.
     *
     * @param array<string, mixed> $options
     *
     * @throws \App\Exceptions\HttpException On insufficient balance.
     */
    public function reserve(
        int $userId,
        int $amount,
        string $sourceModule,
        string $referenceId,
        array $options = []
    ): WalletTransaction;

    /**
     * Release reserved coins back to spendable balance.
     *
     * @param array<string, mixed> $options
     *
     * @throws \App\Exceptions\HttpException On insufficient reserved balance.
     */
    public function release(
        int $userId,
        int $amount,
        string $sourceModule,
        string $referenceId,
        array $options = []
    ): WalletTransaction;
}
