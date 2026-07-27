<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\HomeSectionRepositoryInterface;
use Core\Database\Database;

/**
 * Home section repository — reads active, in-window `home_sections`.
 */
final class HomeSectionRepository implements HomeSectionRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function activeSections(string $now): array
    {
        return $this->db->select(
            'SELECT * FROM `home_sections`'
            . ' WHERE `is_active` = 1'
            . ' AND (`starts_at` IS NULL OR `starts_at` <= ?)'
            . ' AND (`ends_at` IS NULL OR `ends_at` >= ?)'
            . ' ORDER BY `sort_order` ASC, `id` ASC',
            [$now, $now]
        );
    }
}
