<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AppVersionRepositoryInterface;
use Core\Database\Database;

/**
 * App version repository — reads the active `app_versions` row per platform.
 */
final class AppVersionRepository implements AppVersionRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function activeForPlatform(string $platform): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `app_versions` WHERE `platform` = ? AND `is_active` = 1 ORDER BY `id` DESC LIMIT 1',
            [$platform]
        );
    }
}
