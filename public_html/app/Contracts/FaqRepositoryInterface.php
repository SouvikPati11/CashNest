<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * FAQ repository contract — reads `faq_categories` and published `faqs`.
 */
interface FaqRepositoryInterface
{
    /**
     * Active categories ordered by `sort_order`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeCategories(): array;

    /**
     * Published FAQ entries for a locale, optionally scoped to a category slug,
     * ordered by `sort_order`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function publishedFaqs(string $locale, ?string $categorySlug): array;
}
