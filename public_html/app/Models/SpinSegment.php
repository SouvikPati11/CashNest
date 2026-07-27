<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Spin wheel segment (DATABASE_DESIGN.md §C.5).
 */
final class SpinSegment extends BaseModel
{
    public const REWARD_COINS   = 'coins';
    public const REWARD_NOTHING = 'nothing';

    protected string $table = 'spin_wheel_segments';

    /** @var array<int, string> */
    protected array $fillable = [
        'label',
        'reward_type',
        'reward_coins',
        'weight',
        'color_hex',
        'position',
        'is_active',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'           => 'int',
        'reward_coins' => 'int',
        'weight'       => 'int',
        'position'     => 'int',
        'is_active'    => 'bool',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function rewardType(): string
    {
        $type = $this->get('reward_type');

        return is_string($type) ? $type : self::REWARD_COINS;
    }

    public function rewardCoins(): int
    {
        return (int) $this->get('reward_coins', 0);
    }
}
