<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\WalletTransaction;

/**
 * Wallet service contract — the read side of the wallet module.
 *
 * @phpstan-type BalanceView array{
 *     coin_balance: int,
 *     coin_reserved: int,
 *     available: int,
 *     cash_balance: string,
 *     currency: string,
 *     lifetime_earned: int,
 *     lifetime_spent: int
 * }
 * @phpstan-type HistoryResult array{
 *     items: list<WalletTransaction>,
 *     has_more: bool,
 *     next_cursor: ?string,
 *     limit: int,
 *     page: ?int,
 *     use_cursor: bool
 * }
 */
interface WalletServiceInterface
{
    /**
     * Current balance view for a user (zeros if no wallet yet).
     *
     * @return BalanceView
     */
    public function getBalance(int $userId): array;

    /**
     * Coin→cash conversion rate and withdrawal thresholds.
     *
     * @return array{coin_to_cash_rate: string, currency: string, min_withdraw_coins: int, max_withdraw_coins: ?int}
     */
    public function getConversion(): array;

    /**
     * Paginated, filtered ledger history for a user.
     *
     * @param array<string, mixed> $params Validated query params.
     * @return HistoryResult
     *
     * @throws \App\Exceptions\ValidationException On invalid date range.
     */
    public function transactionHistory(int $userId, array $params): array;

    /**
     * A single transaction owned by the user, or null.
     */
    public function findTransactionForUser(string $uuid, int $userId): ?WalletTransaction;
}
