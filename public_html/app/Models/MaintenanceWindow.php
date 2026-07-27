<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Maintenance window (DATABASE_DESIGN.md §L.5).
 *
 * Controls the global maintenance gate. Evaluated server-side so clients cannot
 * bypass it; the status endpoint stays reachable so the app can read the gate.
 */
final class MaintenanceWindow extends BaseModel
{
    protected string $table = 'maintenance_windows';

    /** @var array<int, string> */
    protected array $fillable = [
        'is_enabled',
        'title',
        'message',
        'scheduled_start',
        'scheduled_end',
        'allow_admin_bypass',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'                 => 'int',
        'is_enabled'         => 'bool',
        'allow_admin_bypass' => 'bool',
    ];

    public function isEnabled(): bool
    {
        return (bool) $this->get('is_enabled', false);
    }
}
