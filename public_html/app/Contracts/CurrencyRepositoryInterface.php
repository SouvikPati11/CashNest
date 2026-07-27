<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Currency settings repository contract.
 *
 * Read access to the active `currency_settings` row (conversion rate, currency,
 * withdrawal thresholds).
 */
interface CurrencyRepositoryInterface
{
    /**
     * The active currency settings row, or null if none configured.
     *
     * @return array<string, mixed>|null
     */
    public function getActive(): ?array;
}
