<?php

declare(strict_types=1);

use Core\Env;

/**
 * Rewards module configuration.
 *
 * Values not represented by dedicated schema columns (e.g. the per-day spin
 * limit and default scratch-card expiry). Reward amounts and odds themselves
 * live in their DB config tables (checkin_rewards_config, scratch_card_config,
 * spin_wheel_segments).
 */
return [
    'spin' => [
        'daily_limit' => (int) Env::get('SPIN_DAILY_LIMIT', 3),
    ],
    'scratch' => [
        'expiry_days' => (int) Env::get('SCRATCH_EXPIRY_DAYS', 7),
    ],
];
