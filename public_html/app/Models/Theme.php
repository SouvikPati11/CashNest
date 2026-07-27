<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Runtime theme definition (DATABASE_DESIGN.md §L.9).
 *
 * The single active theme is served to the app at launch and applied app-wide;
 * `default_mode` plus the user's `theme_mode` preference drive runtime light/dark
 * switching.
 */
final class Theme extends BaseModel
{
    public const MODE_SYSTEM = 'system';
    public const MODE_LIGHT  = 'light';
    public const MODE_DARK   = 'dark';

    protected string $table = 'themes';

    /** @var array<int, string> */
    protected array $fillable = [
        'name',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'logo_url',
        'font_family',
        'default_mode',
        'extra',
        'is_active',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'        => 'int',
        'is_active' => 'bool',
        'extra'     => 'json',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }
}
