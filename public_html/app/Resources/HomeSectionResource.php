<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\HomeSection;

/**
 * Home section API resource (API_SPECIFICATION.md §2.80).
 */
final class HomeSectionResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(HomeSection $section): array
    {
        $config = $section->get('config');

        return [
            'type'       => $section->sectionType(),
            'title'      => $section->get('title'),
            'config'     => is_array($config) ? $config : [],
            'sort_order' => (int) $section->get('sort_order', 0),
        ];
    }
}
