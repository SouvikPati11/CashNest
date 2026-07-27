<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\BannerRepositoryInterface;

/**
 * In-memory banner repository for DB-free service tests. Returns active banners
 * for a placement ordered by sort_order (mirrors the DB ordering).
 */
final class InMemoryBannerRepository implements BannerRepositoryInterface
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
            'id'          => $id,
            'placement'   => 'home_top',
            'image_url'   => 'https://cdn/banner.png',
            'action_type' => 'none',
            'sort_order'  => 0,
            'is_active'   => 1,
        ], $data, ['id' => $id]);

        return $id;
    }

    public function activeForPlacement(string $placement, string $now): array
    {
        $rows = array_values(array_filter(
            $this->rows,
            static fn(array $r): bool => (int) $r['is_active'] === 1 && $r['placement'] === $placement
        ));

        usort($rows, static fn(array $a, array $b): int => (int) $a['sort_order'] <=> (int) $b['sort_order']);

        return $rows;
    }
}
