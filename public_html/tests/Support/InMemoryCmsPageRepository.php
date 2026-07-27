<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\CmsPageRepositoryInterface;

/**
 * In-memory CMS page repository for DB-free service tests. Returns the latest
 * published version for a slug + locale.
 */
final class InMemoryCmsPageRepository implements CmsPageRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /**
     * @param array<string, mixed> $data
     */
    public function seed(array $data): void
    {
        $this->rows[] = array_merge([
            'title'        => 'Title',
            'body'         => 'Body',
            'locale'       => 'en',
            'version'      => 1,
            'is_published' => 1,
        ], $data);
    }

    public function findPublished(string $slug, string $locale): ?array
    {
        $matches = array_values(array_filter(
            $this->rows,
            static fn(array $r): bool =>
                $r['slug'] === $slug && $r['locale'] === $locale && (int) $r['is_published'] === 1
        ));

        if ($matches === []) {
            return null;
        }

        usort($matches, static fn(array $a, array $b): int => (int) $b['version'] <=> (int) $a['version']);

        return $matches[0];
    }
}
