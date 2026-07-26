<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Services\RbacService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAdminRoleRepository;

final class RbacServiceTest extends TestCase
{
    private InMemoryAdminRoleRepository $roles;

    private RbacService $rbac;

    protected function setUp(): void
    {
        $this->roles = new InMemoryAdminRoleRepository();
        $this->roles->seed(1, 'super_admin');
        $this->roles->seed(2, 'finance', ['withdraw.view', 'withdraw.approve']);

        $this->rbac = new RbacService($this->roles);
    }

    public function testSuperAdminHoldsEveryPermission(): void
    {
        self::assertTrue($this->rbac->can(1, 'anything.at.all'));
        self::assertTrue($this->rbac->isSuperAdmin(1));
    }

    public function testScopedRoleOnlyHoldsGrantedPermissions(): void
    {
        self::assertTrue($this->rbac->can(2, 'withdraw.view'));
        self::assertTrue($this->rbac->can(2, 'withdraw.approve'));
        self::assertFalse($this->rbac->can(2, 'user.view'));
        self::assertFalse($this->rbac->isSuperAdmin(2));
    }

    public function testUnknownRoleHasNoPermissions(): void
    {
        self::assertFalse($this->rbac->can(99, 'dashboard.view'));
    }
}
