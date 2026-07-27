<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ThemeRepositoryInterface;
use Core\Database\Database;

/**
 * Theme repository — reads the single active `themes` row.
 */
final class ThemeRepository implements ThemeRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function activeTheme(): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `themes` WHERE `is_active` = 1 ORDER BY `id` DESC LIMIT 1'
        );
    }
}
