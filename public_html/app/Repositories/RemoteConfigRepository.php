<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RemoteConfigRepositoryInterface;
use Core\Database\Database;

/**
 * Remote config repository — reads active `remote_configs` for an environment.
 */
final class RemoteConfigRepository implements RemoteConfigRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function activeForEnvironment(string $environment): array
    {
        return $this->db->select(
            'SELECT `config_key`, `value_type`, `value`, `environment`, `updated_at`'
            . ' FROM `remote_configs` WHERE `is_active` = 1 AND `environment` IN (\'all\', ?)'
            . ' ORDER BY `environment` ASC, `id` ASC',
            [$environment]
        );
    }
}
