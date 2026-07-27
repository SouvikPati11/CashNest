<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\CurrencyRepositoryInterface;

/**
 * In-memory currency settings repository for tests.
 */
final class InMemoryCurrencyRepository implements CurrencyRepositoryInterface
{
    /** @var array<string, mixed>|null */
    public ?array $active;

    /**
     * @param array<string, mixed>|null $active
     */
    public function __construct(?array $active = null)
    {
        $this->active = $active ?? [
            'coin_to_cash_rate'  => '0.00100000',
            'currency_code'      => 'INR',
            'min_withdraw_coins' => 5000,
            'max_withdraw_coins' => 100000,
        ];
    }

    public function getActive(): ?array
    {
        return $this->active;
    }
}
