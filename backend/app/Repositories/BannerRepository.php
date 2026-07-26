<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\BannerRepositoryInterface;
use Core\Database\Database;

/**
 * Banner repository — reads active, in-window `banners` for a placement.
 */
final class BannerRepository implements BannerRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function activeForPlacement(string $placement, string $now): array
    {
        return $this->db->select(
            'SELECT * FROM `banners`'
            . ' WHERE `is_active` = 1 AND `placement` = ?'
            . ' AND (`starts_at` IS NULL OR `starts_at` <= ?)'
            . ' AND (`ends_at` IS NULL OR `ends_at` >= ?)'
            . ' ORDER BY `sort_order` ASC, `id` ASC',
            [$placement, $now, $now]
        );
    }
}
