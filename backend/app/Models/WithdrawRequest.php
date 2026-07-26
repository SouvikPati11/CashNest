<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Withdraw request (DATABASE_DESIGN.md §G.2).
 *
 * A user payout request and its lifecycle. Coins are reserved (held) at creation
 * and either settled (paid) or released (rejected/cancelled) — never mutated
 * outside the ledger. The status machine enforced here is:
 *
 *   pending  → approved → paid
 *   pending  → rejected
 *   pending  → cancelled
 */
final class WithdrawRequest extends BaseModel
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_APPROVED   = 'approved';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID       = 'paid';
    public const STATUS_REJECTED   = 'rejected';
    public const STATUS_CANCELLED  = 'cancelled';
    public const STATUS_FAILED     = 'failed';

    /**
     * Allowed status transitions (from => list of reachable to-states).
     *
     * @var array<string, array<int, string>>
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING  => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED => [self::STATUS_PAID],
    ];

    protected string $table = 'withdraw_requests';

    /** @var array<int, string> */
    protected array $fillable = [
        'uuid',
        'user_id',
        'method_id',
        'gateway_id',
        'coins_amount',
        'cash_amount',
        'fee_amount',
        'net_amount',
        'currency_code',
        'conversion_rate',
        'payment_detail',
        'status',
        'hold_transaction_id',
        'debit_transaction_id',
        'refund_transaction_id',
        'admin_id',
        'admin_note',
        'external_reference',
        'requested_ip',
        'processed_at',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'                    => 'int',
        'user_id'               => 'int',
        'method_id'             => 'int',
        'gateway_id'            => 'int',
        'coins_amount'          => 'int',
        'hold_transaction_id'   => 'int',
        'debit_transaction_id'  => 'int',
        'refund_transaction_id' => 'int',
        'admin_id'              => 'int',
        'payment_detail'        => 'json',
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

    public function userId(): int
    {
        return (int) $this->get('user_id', 0);
    }

    public function coinsAmount(): int
    {
        return (int) $this->get('coins_amount', 0);
    }

    public function status(): string
    {
        $status = $this->get('status');

        return is_string($status) ? $status : self::STATUS_PENDING;
    }

    /**
     * Whether a transition from the current status to $to is permitted.
     */
    public function canTransitionTo(string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$this->status()] ?? [], true);
    }
}
