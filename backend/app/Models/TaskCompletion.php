<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Task completion record (DATABASE_DESIGN.md §C.8).
 */
final class TaskCompletion extends BaseModel
{
    public const STATUS_STARTED  = 'started';
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CREDITED = 'credited';

    protected string $table = 'task_completions';

    /** @var array<int, string> */
    protected array $fillable = [
        'user_id',
        'task_id',
        'status',
        'reward_coins',
        'proof_url',
        'verification_ref',
        'transaction_id',
        'reviewed_by',
        'completed_at',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'             => 'int',
        'user_id'        => 'int',
        'task_id'        => 'int',
        'reward_coins'   => 'int',
        'transaction_id' => 'int',
        'reviewed_by'    => 'int',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function status(): string
    {
        $status = $this->get('status');

        return is_string($status) ? $status : self::STATUS_STARTED;
    }
}
