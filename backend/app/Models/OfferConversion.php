<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Offer/CPA conversion (DATABASE_DESIGN.md §D.4).
 */
final class OfferConversion extends BaseModel
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_CREDITED = 'credited';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_REJECTED = 'rejected';

    protected string $table = 'offer_conversions';

    /** @var array<int, string> */
    protected array $fillable = [
        'provider_id',
        'user_id',
        'offer_id',
        'click_id',
        'transaction_id_ext',
        'payout_coins',
        'provider_revenue',
        'status',
        'wallet_transaction_id',
        'ip_address',
        'signature_valid',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'                    => 'int',
        'provider_id'           => 'int',
        'user_id'               => 'int',
        'offer_id'              => 'int',
        'click_id'              => 'int',
        'payout_coins'          => 'int',
        'wallet_transaction_id' => 'int',
        'signature_valid'       => 'bool',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function userId(): int
    {
        return (int) $this->get('user_id', 0);
    }

    public function payoutCoins(): int
    {
        return (int) $this->get('payout_coins', 0);
    }

    public function status(): string
    {
        $status = $this->get('status');

        return is_string($status) ? $status : self::STATUS_PENDING;
    }
}
