<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\AdminRoleRepositoryInterface;

/**
 * In-memory admin role repository for DB-free RBAC tests.
 */
final class InMemoryAdminRoleRepository implements AdminRoleRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $roles = [];

    /** @var array<int, array<int, string>> */
    public array $permissions = [];

    /**
     * @param array<int, string> $permissionSlugs
     */
    public function seed(int $id, string $slug, array $permissionSlugs = [], string $name = ''): void
    {
        $this->roles[$id]       = ['id' => $id, 'slug' => $slug, 'name' => $name !== '' ? $name : ucfirst($slug)];
        $this->permissions[$id] = $permissionSlugs;
    }

    public function all(): array
    {
        return array_values($this->roles);
    }

    public function find(int $id): ?array
    {
        return $this->roles[$id] ?? null;
    }

    public function permissionSlugs(int $roleId): array
    {
        return $this->permissions[$roleId] ?? [];
    }
}
