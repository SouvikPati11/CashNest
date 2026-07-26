<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\RbacService;
use App\Admin\View\AdminView;
use App\Contracts\AdminRoleRepositoryInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Roles & access (RBAC) — view roles and their granted permissions.
 */
final class RoleController extends BaseAdminController
{
    public function __construct(
        AdminView $view,
        AdminAuthService $auth,
        RbacService $rbac,
        private AdminRoleRepositoryInterface $roles
    ) {
        parent::__construct($view, $auth, $rbac);
    }

    public function index(Request $request): Response
    {
        $this->authorize('rbac.view');

        $roles = [];
        foreach ($this->roles->all() as $role) {
            $roles[] = $role + ['permissions' => $this->roles->permissionSlugs((int) $role['id'])];
        }

        return $this->render('roles/index', ['roles' => $roles], 'roles');
    }
}
