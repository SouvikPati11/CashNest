<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AdminRoleRepositoryInterface;
use Core\Database\Database;

/**
 * Admin role repository — reads `admin_roles` and resolves granted permissions
 * via `admin_role_permissions` + `admin_permissions`.
 */
final class AdminRoleRepository implements AdminRoleRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->select('SELECT * FROM `admin_roles` ORDER BY `id` ASC');
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM `admin_roles` WHERE `id` = ? LIMIT 1', [$id]);
    }

    public function permissionSlugs(int $roleId): array
    {
        $rows = $this->db->select(
            'SELECT p.`slug` FROM `admin_role_permissions` rp'
            . ' INNER JOIN `admin_permissions` p ON p.`id` = rp.`permission_id`'
            . ' WHERE rp.`role_id` = ?',
            [$roleId]
        );

        $slugs = [];
        foreach ($rows as $row) {
            if (is_string($row['slug'] ?? null)) {
                $slugs[] = $row['slug'];
            }
        }

        return $slugs;
    }
}
