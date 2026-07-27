<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\WalletTransaction;

/**
 * Referral commission contract.
 *
 * Applied whenever a referred user earns coins: qualifies the referral (paying
 * one-time signup bonuses on first earn) and credits the referrer a commission
 * on the earning. All credits go ONLY through the LedgerService and are
 * idempotent (one commission per source transaction).
 */
interface ReferralCommissionServiceInterface
{
    public function applyForEarning(int $earnerUserId, WalletTransaction $earning): void;
}
