<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Fraud flag repository contract — reads `fraud_flags`.
 *
 * The Withdraw module uses this to enforce the rule that an open high/critical
 * flag holds a user's withdrawals until resolved (DATABASE_DESIGN.md §L).
 */
interface FraudFlagRepositoryInterface
{
    /**
     * Whether the user has an open high/critical flag that blocks withdrawals.
     */
    public function hasActiveWithdrawHold(int $userId): bool;
}
