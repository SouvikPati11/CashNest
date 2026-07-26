<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\Theme;

/**
 * Theme API resource (API_SPECIFICATION.md §2.78).
 */
final class ThemeResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Theme $theme): array
    {
        $extra = $theme->get('extra');

        return [
            'name'             => $theme->get('name'),
            'primary_color'    => $theme->get('primary_color'),
            'secondary_color'  => $theme->get('secondary_color'),
            'accent_color'     => $theme->get('accent_color'),
            'background_color' => $theme->get('background_color'),
            'logo_url'         => $theme->get('logo_url'),
            'font_family'      => $theme->get('font_family'),
            'default_mode'     => $theme->get('default_mode', Theme::MODE_SYSTEM),
            'extra'            => is_array($extra) ? $extra : [],
        ];
    }
}
