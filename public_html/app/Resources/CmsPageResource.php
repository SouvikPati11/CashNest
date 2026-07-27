<?php

declare(strict_types=1);

namespace App\Resources;

use App\Models\CmsPage;

/**
 * CMS page API resource (API_SPECIFICATION.md §2.73).
 */
final class CmsPageResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(CmsPage $page): array
    {
        return [
            'slug'         => $page->slug(),
            'title'        => $page->get('title'),
            'body'         => $page->get('body'),
            'locale'       => $page->get('locale', 'en'),
            'version'      => (int) $page->get('version', 1),
            'effective_at' => $page->get('effective_at'),
        ];
    }
}
