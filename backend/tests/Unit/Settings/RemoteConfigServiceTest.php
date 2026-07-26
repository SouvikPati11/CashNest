<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Services\RemoteConfigService;
use Core\Config;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAppSettingRepository;
use Tests\Support\InMemoryRemoteConfigRepository;

final class RemoteConfigServiceTest extends TestCase
{
    private InMemoryRemoteConfigRepository $remote;

    private InMemoryAppSettingRepository $appSettings;

    private RemoteConfigService $service;

    protected function setUp(): void
    {
        $this->remote      = new InMemoryRemoteConfigRepository();
        $this->appSettings = new InMemoryAppSettingRepository();
        $this->service     = new RemoteConfigService(
            $this->remote,
            $this->appSettings,
            new Config(['app' => ['env' => 'production']])
        );
    }

    public function testCastsValuesByType(): void
    {
        $this->remote->seed('spin_enabled', 'bool', 'true');
        $this->remote->seed('max_daily_spins', 'int', '3');
        $this->remote->seed('rollout', 'float', '0.25');
        $this->remote->seed('flags', 'json', '{"a":1}');

        [$map] = $this->service->effective();

        self::assertTrue($map['spin_enabled']);
        self::assertSame(3, $map['max_daily_spins']);
        self::assertSame(0.25, $map['rollout']);
        self::assertSame(['a' => 1], $map['flags']);
    }

    public function testRemoteConfigOverridesLegacyAppSetting(): void
    {
        $this->appSettings->seed('offerwall_enabled', '0', 'bool');
        $this->remote->seed('offerwall_enabled', 'bool', 'true');

        [$map] = $this->service->effective();

        self::assertTrue($map['offerwall_enabled']);
    }

    public function testEnvironmentSpecificOverridesAll(): void
    {
        $this->remote->seed('feature_x', 'bool', 'false', 'all');
        $this->remote->seed('feature_x', 'bool', 'true', 'production');

        [$map] = $this->service->effective();

        self::assertTrue($map['feature_x']);
    }

    public function testKeysFilterAndEtag(): void
    {
        $this->remote->seed('a', 'int', '1');
        $this->remote->seed('b', 'int', '2');

        [$map, $etag] = $this->service->effective(['a']);

        self::assertArrayHasKey('a', $map);
        self::assertArrayNotHasKey('b', $map);
        self::assertStringStartsWith('cfg_', $etag);
    }
}
