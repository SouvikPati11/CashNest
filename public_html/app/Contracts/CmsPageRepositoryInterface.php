<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * CMS page repository contract — reads published `cms_pages`.
 */
interface CmsPageRepositoryInterface
{
    /**
     * The latest published version of a page for a slug + locale.
     *
     * @return array<string, mixed>|null
     */
    public function findPublished(string $slug, string $locale): ?array;
}
