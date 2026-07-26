<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Wallet.
 *
 * Represents a row of the `wallets` table (DATABASE_DESIGN.md §B.1): the current
 * coin balances for a user, plus reserved (held) amounts. Balances are
 * ledger-backed — they are only ever mutated by the LedgerService alongside an
 * immutable `wallet_transactions` entry, never edited ad hoc.
 */
final class Wallet extends BaseModel
{
    protected string $table = 'wallets';

    protected string $primaryKey = 'id';

    /** @var array<int, string> */
    protected array $fillable = [
        'user_id',
        'coin_balance',
        'coin_reserved',
        'lifetime_coins_earned',
        'lifetime_coins_spent',
        'cash_balance',
        'cash_reserved',
        'version',
        'last_transaction_id',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'                    => 'int',
        'user_id'               => 'int',
        'coin_balance'          => 'int',
        'coin_reserved'         => 'int',
        'lifetime_coins_earned' => 'int',
        'lifetime_coins_spent'  => 'int',
        'version'               => 'int',
        'last_transaction_id'   => 'int',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function coinBalance(): int
    {
        return (int) $this->get('coin_balance', 0);
    }

    public function coinReserved(): int
    {
        return (int) $this->get('coin_reserved', 0);
    }

    /**
     * Spendable balance. Reserved coins are already deducted from coin_balance
     * on hold, so the available amount equals coin_balance (per §B.1).
     */
    public function available(): int
    {
        return $this->coinBalance();
    }
}
