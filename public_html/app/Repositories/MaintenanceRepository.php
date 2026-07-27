<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\MaintenanceRepositoryInterface;
use Core\Database\Database;

/**
 * Maintenance repository — reads the current `maintenance_windows` row.
 */
final class MaintenanceRepository implements MaintenanceRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function current(): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `maintenance_windows` ORDER BY `is_enabled` DESC, `id` DESC LIMIT 1'
        );
    }
}
