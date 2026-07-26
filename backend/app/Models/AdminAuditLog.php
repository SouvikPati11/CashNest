<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Admin audit log entry (DATABASE_DESIGN.md §I.5).
 *
 * Immutable, append-only record of a mutating admin action with actor, target,
 * and before/after snapshots.
 */
final class AdminAuditLog extends BaseModel
{
    protected string $table = 'admin_audit_logs';

    /** @var array<int, string> */
    protected array $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'before_data',
        'after_data',
        'ip_address',
        'user_agent',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'          => 'int',
        'admin_id'    => 'int',
        'target_id'   => 'int',
        'before_data' => 'json',
        'after_data'  => 'json',
    ];
}
