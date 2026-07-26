<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Scratch card (DATABASE_DESIGN.md §C.3).
 */
final class ScratchCard extends BaseModel
{
    public const STATUS_ISSUED   = 'issued';
    public const STATUS_REVEALED = 'revealed';
    public const STATUS_CLAIMED  = 'claimed';
    public const STATUS_EXPIRED  = 'expired';

    protected string $table = 'scratch_cards';

    /** @var array<int, string> */
    protected array $fillable = [
        'user_id',
        'source',
        'reward_coins',
        'status',
        'revealed_at',
        'claimed_at',
        'expires_at',
        'transaction_id',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'             => 'int',
        'user_id'        => 'int',
        'reward_coins'   => 'int',
        'transaction_id' => 'int',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function status(): string
    {
        $status = $this->get('status');

        return is_string($status) ? $status : self::STATUS_ISSUED;
    }

    public function rewardCoins(): int
    {
        return (int) $this->get('reward_coins', 0);
    }

    public function isExpired(int $now): bool
    {
        $expires = $this->get('expires_at');

        if (!is_string($expires) || $expires === '') {
            return false;
        }

        $timestamp = strtotime($expires . ' UTC');

        return $timestamp !== false && $timestamp <= $now;
    }
}
