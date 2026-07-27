<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Referral link (DATABASE_DESIGN.md §E.1).
 */
final class Referral extends BaseModel
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_REWARDED  = 'rewarded';
    public const STATUS_REJECTED  = 'rejected';

    protected string $table = 'referrals';

    /** @var array<int, string> */
    protected array $fillable = [
        'referrer_id',
        'referee_id',
        'referral_code',
        'status',
        'signup_bonus_coins',
        'referee_bonus_coins',
        'qualified_at',
        'rewarded_at',
        'referrer_transaction_id',
        'referee_transaction_id',
        'signup_ip',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'                  => 'int',
        'referrer_id'         => 'int',
        'referee_id'          => 'int',
        'signup_bonus_coins'  => 'int',
        'referee_bonus_coins' => 'int',
    ];

    public function id(): int
    {
        return (int) $this->get('id', 0);
    }

    public function referrerId(): int
    {
        return (int) $this->get('referrer_id', 0);
    }

    public function refereeId(): int
    {
        return (int) $this->get('referee_id', 0);
    }

    public function status(): string
    {
        $status = $this->get('status');

        return is_string($status) ? $status : self::STATUS_PENDING;
    }
}
