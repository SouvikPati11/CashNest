<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Admin role (DATABASE_DESIGN.md §I.2).
 *
 * Groups permissions. `super_admin` implicitly retains all permissions;
 * `is_system` roles are protected from deletion.
 */
final class AdminRole extends BaseModel
{
    public const SUPER_ADMIN = 'super_admin';

    protected string $table = 'admin_roles';

    /** @var array<int, string> */
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'        => 'int',
        'is_system' => 'bool',
    ];

    public function id(): ?int
    {
        $id = $this->get('id');

        return $id === null ? null : (int) $id;
    }

    public function slug(): string
    {
        $slug = $this->get('slug');

        return is_string($slug) ? $slug : '';
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug() === self::SUPER_ADMIN;
    }
}
