<?php

declare(strict_types=1);

namespace App\Models;

/**
 * A recorded spin (DATABASE_DESIGN.md §C.6 `spin_history`).
 */
final class SpinResult extends BaseModel
{
    protected string $table = 'spin_history';

    /** @var array<int, string> */
    protected array $fillable = ['user_id', 'segment_id', 'reward_coins', 'spin_date', 'source', 'transaction_id'];

    /** @var array<string, string> */
    protected array $casts = [
        'id'             => 'int',
        'user_id'        => 'int',
        'segment_id'     => 'int',
        'reward_coins'   => 'int',
        'transaction_id' => 'int',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function rewardCoins(): int
    {
        return (int) $this->get('reward_coins', 0);
    }
}
