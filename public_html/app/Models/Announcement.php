<?php

declare(strict_types=1);

namespace App\Models;

/**
 * In-app announcement (DATABASE_DESIGN.md §L.2).
 *
 * Bar/popup/card announcements with priority and scheduling. Per-user dismissal
 * is tracked in `announcement_reads`. Addressed by numeric id.
 */
final class Announcement extends BaseModel
{
    public const DISPLAY_BAR   = 'bar';
    public const DISPLAY_POPUP = 'popup';
    public const DISPLAY_CARD  = 'card';

    protected string $table = 'announcements';

    /** @var array<int, string> */
    protected array $fillable = [
        'title',
        'body',
        'display_type',
        'priority',
        'action_type',
        'action_value',
        'target_audience',
        'is_dismissible',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'              => 'int',
        'priority'        => 'int',
        'is_dismissible'  => 'bool',
        'is_active'       => 'bool',
        'target_audience' => 'json',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }
}
