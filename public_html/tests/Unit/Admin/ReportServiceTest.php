<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Services\ReportService;
use App\Admin\Support\CsvExporter;
use App\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAdminQueryRepository;

final class ReportServiceTest extends TestCase
{
    private ReportService $service;

    protected function setUp(): void
    {
        $query = new InMemoryAdminQueryRepository();
        $query->seed('users', [
            [
                'id' => 1, 'uuid' => 'u-1', 'name' => 'Ann',
                'email' => 'ann@x.io', 'status' => 'active', 'created_at' => '2026-01-01',
            ],
        ]);

        $this->service = new ReportService($query, new CsvExporter());
    }

    public function testListsAvailableReports(): void
    {
        $reports = $this->service->available();

        self::assertArrayHasKey('users', $reports);
        self::assertArrayHasKey('withdrawals', $reports);
    }

    public function testExportsCsvWithHeader(): void
    {
        $csv = $this->service->exportCsv('users');

        $lines = array_values(array_filter(explode("\n", trim($csv))));
        self::assertSame('id,uuid,name,email,status,created_at', trim($lines[0]));
        self::assertStringContainsString('Ann', $lines[1]);
    }

    public function testUnknownReportThrows(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->exportCsv('nope');
    }
}
