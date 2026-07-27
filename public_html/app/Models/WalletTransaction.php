<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Wallet transaction (ledger entry).
 *
 * Represents a row of the immutable `wallet_transactions` ledger
 * (DATABASE_DESIGN.md §B.2). Every coin movement is exactly one row; rows are
 * never updated or deleted (corrections are new `reversal` rows). `reference_id`
 * is the idempotency key and is not exposed to clients.
 */
final class WalletTransaction extends BaseModel
{
    public const DIRECTION_CREDIT = 'credit';
    public const DIRECTION_DEBIT  = 'debit';

    public const TYPE_WITHDRAWAL_HOLD    = 'withdrawal_hold';
    public const TYPE_WITHDRAWAL_RELEASE = 'withdrawal_release';

    /** All valid ledger types (mirrors the DB enum). */
    public const TYPES = [
        'checkin',
        'scratch',
        'spin',
        'task',
        'offerwall',
        'cpa',
        'referral',
        'referral_commission',
        'withdrawal_hold',
        'withdrawal_release',
        'withdrawal_debit',
        'rewarded_ad',
        'admin_credit',
        'admin_debit',
        'reversal',
        'adjustment',
    ];

    protected string $table = 'wallet_transactions';

    protected string $primaryKey = 'id';

    /** @var array<int, string> */
    protected array $fillable = [
        'uuid',
        'user_id',
        'wallet_id',
        'direction',
        'amount',
        'cash_amount',
        'balance_after',
        'type',
        'source_module',
        'source_id',
        'reference_id',
        'related_transaction_id',
        'performed_by_admin_id',
        'description',
        'metadata',
    ];

    /** reference_id is an internal idempotency key; never expose it. */
    protected array $hidden = [
        'reference_id',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'                     => 'int',
        'user_id'                => 'int',
        'wallet_id'              => 'int',
        'amount'                 => 'int',
        'balance_after'          => 'int',
        'source_id'              => 'int',
        'related_transaction_id' => 'int',
        'performed_by_admin_id'  => 'int',
        'metadata'               => 'json',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function uuid(): ?string
    {
        $uuid = $this->get('uuid');

        return is_string($uuid) ? $uuid : null;
    }

    public function amount(): int
    {
        return (int) $this->get('amount', 0);
    }

    public function direction(): string
    {
        $direction = $this->get('direction');

        return is_string($direction) ? $direction : '';
    }
}
