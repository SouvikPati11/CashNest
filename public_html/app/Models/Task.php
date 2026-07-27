<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Task catalog entry (DATABASE_DESIGN.md §C.7).
 */
final class Task extends BaseModel
{
    public const VERIFY_AUTO     = 'auto';
    public const VERIFY_MANUAL   = 'manual';
    public const VERIFY_CALLBACK = 'callback';

    protected string $table = 'tasks';

    /** @var array<int, string> */
    protected array $fillable = [
        'title',
        'description',
        'task_type',
        'reward_coins',
        'action_url',
        'verification_type',
        'max_completions',
        'per_user_limit',
        'icon_url',
        'starts_at',
        'ends_at',
        'is_active',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'              => 'int',
        'reward_coins'    => 'int',
        'max_completions' => 'int',
        'per_user_limit'  => 'int',
        'sort_order'      => 'int',
        'is_active'       => 'bool',
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

    public function verificationType(): string
    {
        $type = $this->get('verification_type');

        return is_string($type) ? $type : self::VERIFY_MANUAL;
    }

    public function perUserLimit(): int
    {
        return max(1, (int) $this->get('per_user_limit', 1));
    }

    /**
     * Whether the task is active and within its availability window.
     */
    public function isAvailable(int $now): bool
    {
        if (!(bool) $this->get('is_active', false)) {
            return false;
        }

        $start = $this->get('starts_at');
        if (is_string($start) && $start !== '' && ($ts = strtotime($start . ' UTC')) !== false && $ts > $now) {
            return false;
        }

        $end = $this->get('ends_at');
        if (is_string($end) && $end !== '' && ($te = strtotime($end . ' UTC')) !== false && $te < $now) {
            return false;
        }

        return true;
    }
}
