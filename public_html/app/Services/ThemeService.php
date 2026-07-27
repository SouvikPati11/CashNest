<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ThemeRepositoryInterface;
use App\Contracts\ThemeServiceInterface;
use App\Models\Theme;
use App\Resources\ThemeResource;

/**
 * Theme service — serves the active runtime theme.
 *
 * Returns the single active theme (with a safe built-in fallback so the app
 * always has valid tokens) plus an ETag for client caching. Runtime light/dark
 * switching is driven by the theme's `default_mode` and the user's `theme_mode`
 * preference (managed by the settings service).
 */
final class ThemeService implements ThemeServiceInterface
{
    /** Safe fallback applied when no active theme is configured. */
    private const FALLBACK = [
        'name'             => 'Default',
        'primary_color'    => '#1E88E5',
        'secondary_color'  => '#42A5F5',
        'accent_color'     => '#FFC107',
        'background_color' => '#FFFFFF',
        'logo_url'         => null,
        'font_family'      => 'Inter',
        'default_mode'     => Theme::MODE_SYSTEM,
        'extra'            => [],
    ];

    public function __construct(private ThemeRepositoryInterface $themes)
    {
    }

    public function activeTheme(): array
    {
        $row = $this->themes->activeTheme();

        $payload = $row === null
            ? self::FALLBACK
            : ThemeResource::toArray(Theme::fromRow($row));

        $etag = 'theme_' . substr(hash('sha256', (string) json_encode($payload)), 0, 12);

        return [$payload, $etag];
    }
}
