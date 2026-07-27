<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AppSettingRepositoryInterface;
use Core\Database\Database;

/**
 * App setting repository — reads public `app_settings`.
 */
final class AppSettingRepository implements AppSettingRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function publicSettings(): array
    {
        return $this->db->select(
            'SELECT `setting_key`, `setting_value`, `value_type` FROM `app_settings` WHERE `is_public` = 1'
        );
    }
}
