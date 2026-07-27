<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Exceptions\ValidationException;
use App\Services\AppVersionService;
use App\Services\MaintenanceService;
use App\Services\SettingsService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAppSettingRepository;
use Tests\Support\InMemoryAppVersionRepository;
use Tests\Support\InMemoryMaintenanceRepository;
use Tests\Support\InMemoryUserSettingsRepository;

final class SettingsServiceTest extends TestCase
{
    private const USER = 1;

    private InMemoryUserSettingsRepository $userSettings;

    private InMemoryAppSettingRepository $appSettings;

    private InMemoryAppVersionRepository $versions;

    private InMemoryMaintenanceRepository $maintenance;

    private SettingsService $service;

    protected function setUp(): void
    {
        $this->userSettings = new InMemoryUserSettingsRepository();
        $this->appSettings  = new InMemoryAppSettingRepository();
        $this->versions     = new InMemoryAppVersionRepository();
        $this->maintenance  = new InMemoryMaintenanceRepository();

        $this->service = new SettingsService(
            $this->userSettings,
            $this->appSettings,
            new AppVersionService($this->versions),
            new MaintenanceService($this->maintenance)
        );
    }

    public function testGetReturnsDefaultsAndTypedAppConfig(): void
    {
        $this->appSettings->seed('feature_new_home', '1', 'bool');

        $result = $this->service->getSettings(self::USER);

        self::assertTrue($result['preferences']['notif_push_enabled']);
        self::assertSame('en', $result['preferences']['language']);
        self::assertSame('system', $result['preferences']['theme_mode']);
        self::assertTrue($result['app_config']['feature_new_home']);
    }

    public function testUpdateAppliesPreferences(): void
    {
        $prefs = $this->service->updateSettings(self::USER, ['theme_mode' => 'dark', 'language' => 'hi']);

        self::assertSame('dark', $prefs['theme_mode']);
        self::assertSame('hi', $prefs['language']);
        self::assertSame('dark', $this->service->getSettings(self::USER)['preferences']['theme_mode']);
    }

    public function testUpdateRejectsInvalidThemeMode(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->updateSettings(self::USER, ['theme_mode' => 'neon']);
    }

    public function testAppStatusCombinesVersionAndMaintenance(): void
    {
        $this->versions->seed('android', [
            'latest_version'      => '1.5.0',
            'latest_version_code' => 150,
            'min_supported_code'  => 120,
            'force_update'        => 1,
        ]);
        $this->maintenance->window = ['is_enabled' => 1, 'message' => 'Back soon'];

        $status = $this->service->appStatus('android', 110);

        self::assertSame('1.5.0', $status['latest_version']);
        self::assertTrue($status['force_update']);
        self::assertTrue($status['maintenance']['enabled']);
        self::assertSame('Back soon', $status['maintenance']['message']);
    }
}
