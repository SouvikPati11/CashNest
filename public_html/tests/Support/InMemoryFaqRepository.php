<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\FaqRepositoryInterface;

/**
 * In-memory FAQ repository for DB-free service tests.
 */
final class InMemoryFaqRepository implements FaqRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $categories = [];

    /** @var array<int, array<string, mixed>> */
    public array $faqs = [];

    public function seedCategory(int $id, string $name, string $slug, int $sortOrder = 0): void
    {
        $this->categories[] = ['id' => $id, 'name' => $name, 'slug' => $slug, 'sort_order' => $sortOrder];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function seedFaq(array $data): void
    {
        $this->faqs[] = array_merge([
            'category_id'  => null,
            'question'     => 'Q',
            'answer'       => 'A',
            'locale'       => 'en',
            'sort_order'   => 0,
            'is_published' => 1,
        ], $data);
    }

    public function activeCategories(): array
    {
        $rows = $this->categories;
        usort($rows, static fn(array $a, array $b): int => (int) $a['sort_order'] <=> (int) $b['sort_order']);

        return $rows;
    }

    public function publishedFaqs(string $locale, ?string $categorySlug): array
    {
        $categoryId = null;
        if ($categorySlug !== null) {
            foreach ($this->categories as $category) {
                if ($category['slug'] === $categorySlug) {
                    $categoryId = (int) $category['id'];
                }
            }
        }

        $matcher = static function (array $f) use ($locale, $categorySlug, $categoryId): bool {
            if ((int) $f['is_published'] !== 1 || $f['locale'] !== $locale) {
                return false;
            }

            return $categorySlug === null || (int) ($f['category_id'] ?? 0) === $categoryId;
        };

        return array_values(array_filter($this->faqs, $matcher));
    }
}
