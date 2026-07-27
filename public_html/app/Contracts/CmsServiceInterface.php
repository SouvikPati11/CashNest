<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * CMS service contract — content pages and grouped FAQ.
 */
interface CmsServiceInterface
{
    /**
     * A published content page by slug + locale (falls back to `en`).
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function page(string $slug, string $locale): array;

    /**
     * FAQ entries grouped by category for a locale.
     *
     * @return array<int, array<string, mixed>>
     */
    public function faq(string $locale, ?string $categorySlug): array;
}
