<?php

/**
 * Withdraw module configuration.
 *
 * `kyc_required_threshold_coins` — a payout whose `coins_amount` is at or above
 * this value requires an approved KYC record. Set to 0 to require KYC for every
 * withdrawal regardless of amount.
 */

declare(strict_types=1);

use Core\Env;

return [
    'kyc_required_threshold_coins' => (int) Env::get('WITHDRAW_KYC_THRESHOLD_COINS', 50000),
];
