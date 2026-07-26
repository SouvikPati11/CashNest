<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\HomeSectionRepositoryInterface;

/**
 * In-memory home section repository for DB-free service tests. Returns active
 * sections ordered by sort_order (mirrors the DB ordering).
 */
final class InMemoryHomeSectionRepository implements HomeSectionRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    /**
     * @param array<string, mixed> $data
     */
    public function seed(array $data): int
    {
        $id = $this->nextId++;

        $this->rows[$id] = array_merge([
            'id'           => $id,
            'section_type' => 'custom',
            'sort_order'   => 0,
            'is_active'    => 1,
        ], $data, ['id' => $id]);

        return $id;
    }

    public function activeSections(string $now): array
    {
        $rows = array_values(array_filter($this->rows, static fn(array $r): bool => (int) $r['is_active'] === 1));

        usort($rows, static fn(array $a, array $b): int => (int) $a['sort_order'] <=> (int) $b['sort_order']);

        return $rows;
    }
}
