<?php

declare(strict_types=1);

namespace App\Admin\Services;

use App\Contracts\AdminRoleRepositoryInterface;
use App\Models\AdminRole;

/**
 * Role-based access control for the admin panel.
 *
 * Resolves a role's granted permission slugs (cached per request) and answers
 * capability checks. The `super_admin` role implicitly holds every permission.
 */
final class RbacService
{
    /** @var array<int, array<int, string>> */
    private array $slugCache = [];

    /** @var array<int, bool> */
    private array $superCache = [];

    public function __construct(private AdminRoleRepositoryInterface $roles)
    {
    }

    /**
     * Permission slugs granted to a role.
     *
     * @return array<int, string>
     */
    public function permissions(int $roleId): array
    {
        return $this->slugCache[$roleId] ??= $this->roles->permissionSlugs($roleId);
    }

    public function isSuperAdmin(int $roleId): bool
    {
        if (!isset($this->superCache[$roleId])) {
            $role = $this->roles->find($roleId);
            $slug = is_array($role) && is_string($role['slug'] ?? null) ? $role['slug'] : '';
            $this->superCache[$roleId] = $slug === AdminRole::SUPER_ADMIN;
        }

        return $this->superCache[$roleId];
    }

    /**
     * Whether a role may perform the given permission.
     */
    public function can(int $roleId, string $permission): bool
    {
        if ($this->isSuperAdmin($roleId)) {
            return true;
        }

        return in_array($permission, $this->permissions($roleId), true);
    }
}
