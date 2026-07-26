<?php

declare(strict_types=1);

namespace App\Models;

/**
 * CMS content page (DATABASE_DESIGN.md §L.7).
 *
 * Versioned, locale-aware legal/content pages served by slug. Body is stored
 * pre-sanitized; only the latest published version per slug+locale is served.
 */
final class CmsPage extends BaseModel
{
    protected string $table = 'cms_pages';

    /** @var array<int, string> */
    protected array $fillable = [
        'slug',
        'title',
        'body',
        'locale',
        'version',
        'is_published',
        'effective_at',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'           => 'int',
        'version'      => 'int',
        'is_published' => 'bool',
    ];

    public function slug(): string
    {
        $slug = $this->get('slug');

        return is_string($slug) ? $slug : '';
    }
}
