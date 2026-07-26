<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Per-user settings (DATABASE_DESIGN.md §A.6).
 *
 * Notification toggles plus language and theme mode. Created lazily on first
 * write; sensible defaults apply when absent.
 */
final class UserSettings extends BaseModel
{
    public const THEME_SYSTEM = 'system';
    public const THEME_LIGHT  = 'light';
    public const THEME_DARK   = 'dark';

    /** Allowed theme modes (mirrors the DB enum). */
    public const THEME_MODES = [self::THEME_SYSTEM, self::THEME_LIGHT, self::THEME_DARK];

    protected string $table = 'user_settings';

    /** @var array<int, string> */
    protected array $fillable = [
        'user_id',
        'notif_push_enabled',
        'notif_transactional',
        'notif_promotional',
        'language',
        'theme_mode',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'                  => 'int',
        'user_id'             => 'int',
        'notif_push_enabled'  => 'bool',
        'notif_transactional' => 'bool',
        'notif_promotional'   => 'bool',
    ];
}
