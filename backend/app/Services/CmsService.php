<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CmsPageRepositoryInterface;
use App\Contracts\CmsServiceInterface;
use App\Contracts\FaqRepositoryInterface;
use App\Exceptions\NotFoundException;
use App\Models\CmsPage;
use App\Resources\CmsPageResource;

/**
 * CMS service — content pages and grouped FAQ.
 *
 * Serves the latest published page version for a slug + locale, falling back to
 * English when the requested locale is missing. FAQ entries are grouped under
 * their active categories.
 */
final class CmsService implements CmsServiceInterface
{
    private const FALLBACK_LOCALE = 'en';

    public function __construct(
        private CmsPageRepositoryInterface $pages,
        private FaqRepositoryInterface $faqs
    ) {
    }

    public function page(string $slug, string $locale): array
    {
        $locale = $locale !== '' ? $locale : self::FALLBACK_LOCALE;

        $row = $this->pages->findPublished($slug, $locale);

        if ($row === null && $locale !== self::FALLBACK_LOCALE) {
            $row = $this->pages->findPublished($slug, self::FALLBACK_LOCALE);
        }

        if ($row === null) {
            throw new NotFoundException('Content page not found.');
        }

        return CmsPageResource::toArray(CmsPage::fromRow($row));
    }

    public function faq(string $locale, ?string $categorySlug): array
    {
        $locale = $locale !== '' ? $locale : self::FALLBACK_LOCALE;

        $categories = $this->faqs->activeCategories();
        $faqs       = $this->faqs->publishedFaqs($locale, $categorySlug);

        $byCategory = [];
        foreach ($faqs as $faq) {
            $categoryId = $faq['category_id'] === null ? 0 : (int) $faq['category_id'];
            $byCategory[$categoryId][] = [
                'question' => $faq['question'] ?? '',
                'answer'   => $faq['answer'] ?? '',
            ];
        }

        $groups = [];
        foreach ($categories as $category) {
            $id = (int) $category['id'];
            if (!isset($byCategory[$id])) {
                continue;
            }
            $groups[] = [
                'category' => $category['name'] ?? '',
                'slug'     => $category['slug'] ?? '',
                'items'    => $byCategory[$id],
            ];
        }

        // Uncategorised FAQs (category_id NULL) grouped last.
        if (isset($byCategory[0])) {
            $groups[] = ['category' => 'General', 'slug' => 'general', 'items' => $byCategory[0]];
        }

        return $groups;
    }
}
