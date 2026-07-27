<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Admin permission (DATABASE_DESIGN.md §I.3).
 *
 * A granular `module.action` capability checked by RBAC before each admin action.
 */
final class AdminPermission extends BaseModel
{
    protected string $table = 'admin_permissions';

    /** @var array<int, string> */
    protected array $fillable = [
        'module',
        'action',
        'slug',
        'description',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id' => 'int',
    ];

    public function slug(): string
    {
        $slug = $this->get('slug');

        return is_string($slug) ? $slug : '';
    }
}
