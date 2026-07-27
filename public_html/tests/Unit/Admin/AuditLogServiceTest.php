<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Services\AuditLogService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAdminAuditLogRepository;

final class AuditLogServiceTest extends TestCase
{
    private InMemoryAdminAuditLogRepository $repo;

    private AuditLogService $audit;

    protected function setUp(): void
    {
        $this->repo  = new InMemoryAdminAuditLogRepository();
        $this->audit = new AuditLogService($this->repo);
    }

    public function testLogEncodesSnapshots(): void
    {
        $this->audit->log(7, 'user.status', [
            'target_type' => 'users',
            'target_id'   => 42,
            'before'      => ['status' => 'active'],
            'after'       => ['status' => 'banned'],
            'ip'          => '1.2.3.4',
        ]);

        self::assertCount(1, $this->repo->rows);
        $row = $this->repo->rows[1];
        self::assertSame(7, $row['admin_id']);
        self::assertSame('user.status', $row['action']);
        self::assertSame(42, $row['target_id']);
        self::assertSame('{"status":"active"}', $row['before_data']);
        self::assertSame('{"status":"banned"}', $row['after_data']);
    }

    public function testRecentPaginatesAndFilters(): void
    {
        $this->audit->log(1, 'admin.login');
        $this->audit->log(1, 'user.status');
        $this->audit->log(1, 'user.status');

        $all = $this->audit->recent(null, null, 1, 20);
        self::assertCount(3, $all['rows']);

        $filtered = $this->audit->recent('user.status', null, 1, 20);
        self::assertCount(2, $filtered['rows']);
    }
}
