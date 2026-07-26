<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Admin role repository contract — reads `admin_roles` and their permissions.
 */
interface AdminRoleRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array;

    /**
     * Permission slugs granted to a role.
     *
     * @return array<int, string>
     */
    public function permissionSlugs(int $roleId): array;
}
