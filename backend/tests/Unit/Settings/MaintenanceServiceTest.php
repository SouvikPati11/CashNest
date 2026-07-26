<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Services\MaintenanceService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryMaintenanceRepository;

final class MaintenanceServiceTest extends TestCase
{
    private InMemoryMaintenanceRepository $repo;

    private MaintenanceService $service;

    protected function setUp(): void
    {
        $this->repo    = new InMemoryMaintenanceRepository();
        $this->service = new MaintenanceService($this->repo);
    }

    public function testDisabledWhenNoWindow(): void
    {
        self::assertFalse($this->service->status()['enabled']);
    }

    public function testEnabledWithoutSchedule(): void
    {
        $this->repo->window = ['is_enabled' => 1, 'title' => 'Maintenance', 'message' => 'Soon'];

        $status = $this->service->status();

        self::assertTrue($status['enabled']);
        self::assertSame('Maintenance', $status['title']);
    }

    public function testDisabledWhenScheduledInFuture(): void
    {
        $future = gmdate('Y-m-d H:i:s', time() + 86400);
        $this->repo->window = ['is_enabled' => 1, 'scheduled_start' => $future];

        self::assertFalse($this->service->status()['enabled']);
    }
}
