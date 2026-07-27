<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Services\AuditLogService;
use App\Admin\Services\FraudAdminService;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAdminAuditLogRepository;
use Tests\Support\InMemoryAdminFraudRepository;

final class FraudAdminServiceTest extends TestCase
{
    private InMemoryAdminFraudRepository $fraud;

    private InMemoryAdminAuditLogRepository $auditRepo;

    private FraudAdminService $service;

    protected function setUp(): void
    {
        $this->fraud     = new InMemoryAdminFraudRepository();
        $this->auditRepo = new InMemoryAdminAuditLogRepository();
        $this->service   = new FraudAdminService($this->fraud, new AuditLogService($this->auditRepo));
    }

    public function testListsByStatus(): void
    {
        $this->fraud->seed(['status' => 'open']);
        $this->fraud->seed(['status' => 'dismissed']);

        self::assertCount(1, $this->service->list('open', 1, 20)['rows']);
        self::assertCount(2, $this->service->list(null, 1, 20)['rows']);
    }

    public function testResolveUpdatesAndAudits(): void
    {
        $id = $this->fraud->seed(['status' => 'open']);

        $this->service->resolve(3, $id, 'confirmed', 'suspended', '1.2.3.4');

        self::assertSame('confirmed', $this->fraud->rows[$id]['status']);
        self::assertSame('suspended', $this->fraud->rows[$id]['action_taken']);
        self::assertSame('fraud.resolve', $this->auditRepo->rows[1]['action']);
    }

    public function testResolveRejectsInvalidStatus(): void
    {
        $id = $this->fraud->seed(['status' => 'open']);

        $this->expectException(ValidationException::class);
        $this->service->resolve(3, $id, 'bogus', 'none', '1.2.3.4');
    }

    public function testResolveMissingFlagThrows(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->resolve(3, 999, 'confirmed', 'none', '1.2.3.4');
    }
}
