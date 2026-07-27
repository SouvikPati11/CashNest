<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Services\AuditLogService;
use App\Admin\Services\UserAdminService;
use App\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAdminAuditLogRepository;
use Tests\Support\InMemoryAdminQueryRepository;
use Tests\Support\InMemoryUserRepository;

final class UserAdminServiceTest extends TestCase
{
    private InMemoryUserRepository $users;

    private InMemoryAdminAuditLogRepository $auditRepo;

    private UserAdminService $service;

    protected function setUp(): void
    {
        $query = new InMemoryAdminQueryRepository();
        $query->seed('users', [
            ['id' => 1, 'name' => 'Ann', 'email' => 'ann@x.io', 'status' => 'active'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@x.io', 'status' => 'active'],
        ]);

        $this->users     = new InMemoryUserRepository();
        $this->auditRepo = new InMemoryAdminAuditLogRepository();
        $this->users->create(['uuid' => 'u-1', 'name' => 'Ann', 'email' => 'ann@x.io', 'status' => 'active']);

        $this->service = new UserAdminService($query, $this->users, new AuditLogService($this->auditRepo));
    }

    public function testListsUsers(): void
    {
        $result = $this->service->list(null, 1, 20);

        self::assertCount(2, $result['rows']);
        self::assertSame(2, $result['paginator']->total);
    }

    public function testSetStatusUpdatesAndAudits(): void
    {
        $this->service->setStatus(5, 1, 'banned', '1.2.3.4');

        self::assertSame('banned', $this->users->rows[1]['status']);
        self::assertSame('user.status', $this->auditRepo->rows[1]['action']);
        self::assertSame(5, $this->auditRepo->rows[1]['admin_id']);
    }

    public function testSetStatusRejectsInvalidValue(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->setStatus(5, 1, 'exploded', '1.2.3.4');
    }
}
