<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Services\AppVersionService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAppVersionRepository;

final class AppVersionServiceTest extends TestCase
{
    private InMemoryAppVersionRepository $versions;

    private AppVersionService $service;

    protected function setUp(): void
    {
        $this->versions = new InMemoryAppVersionRepository();
        $this->service  = new AppVersionService($this->versions);

        $this->versions->seed('android', [
            'latest_version'      => '1.5.0',
            'latest_version_code' => 150,
            'min_supported_code'  => 120,
            'force_update'        => 1,
            'store_url'           => 'https://play',
        ]);
    }

    public function testForceUpdateBelowMinimum(): void
    {
        $info = $this->service->versionInfo('android', 110);

        self::assertTrue($info['force_update']);
        self::assertTrue($info['update_available']);
        self::assertSame(150, $info['latest_version_code']);
    }

    public function testNoForceUpdateOnLatest(): void
    {
        $info = $this->service->versionInfo('android', 150);

        self::assertFalse($info['force_update']);
        self::assertFalse($info['update_available']);
    }

    public function testUnknownPlatformReturnsDefaults(): void
    {
        $info = $this->service->versionInfo('ios', 100);

        self::assertSame('ios', $info['platform']);
        self::assertNull($info['latest_version']);
        self::assertFalse($info['force_update']);
    }
}
