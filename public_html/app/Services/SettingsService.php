<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AppSettingRepositoryInterface;
use App\Contracts\AppVersionServiceInterface;
use App\Contracts\MaintenanceServiceInterface;
use App\Contracts\SettingsServiceInterface;
use App\Contracts\UserSettingsRepositoryInterface;
use App\Exceptions\ValidationException;
use App\Models\AppSetting;
use App\Models\UserSettings;

/**
 * Settings service.
 *
 * Serves and updates a user's preferences (notification toggles, language,
 * theme mode) and exposes the public app config plus the combined app-status
 * (version + maintenance) summary. Preferences fall back to schema defaults
 * when the user has no settings row yet.
 */
final class SettingsService implements SettingsServiceInterface
{
    private const DEFAULT_LANGUAGE = 'en';

    public function __construct(
        private UserSettingsRepositoryInterface $userSettings,
        private AppSettingRepositoryInterface $appSettings,
        private AppVersionServiceInterface $appVersion,
        private MaintenanceServiceInterface $maintenance
    ) {
    }

    public function getSettings(int $userId): array
    {
        return [
            'preferences' => $this->preferences($userId),
            'app_config'  => $this->appConfig(),
        ];
    }

    public function updateSettings(int $userId, array $changes): array
    {
        $data = [];

        foreach (['notif_push_enabled', 'notif_transactional', 'notif_promotional'] as $toggle) {
            if (array_key_exists($toggle, $changes)) {
                $data[$toggle] = filter_var($changes[$toggle], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
        }

        if (array_key_exists('language', $changes)) {
            $language = is_string($changes['language']) ? trim($changes['language']) : '';
            if ($language === '' || mb_strlen($language) > 10) {
                throw new ValidationException(['language' => ['The language is invalid.']]);
            }
            $data['language'] = $language;
        }

        if (array_key_exists('theme_mode', $changes)) {
            $mode = is_string($changes['theme_mode']) ? $changes['theme_mode'] : '';
            if (!in_array($mode, UserSettings::THEME_MODES, true)) {
                throw new ValidationException(['theme_mode' => ['The theme mode is invalid.']]);
            }
            $data['theme_mode'] = $mode;
        }

        if ($data !== []) {
            $this->userSettings->upsert($userId, $data);
        }

        return $this->preferences($userId);
    }

    public function appStatus(string $platform, ?int $clientVersionCode): array
    {
        $version     = $this->appVersion->versionInfo($platform, $clientVersionCode);
        $maintenance = $this->maintenance->status();

        return [
            'latest_version'     => $version['latest_version'] ?? null,
            'min_supported_code' => $version['min_supported_code'] ?? null,
            'force_update'       => $version['force_update'] ?? false,
            'maintenance'        => [
                'enabled' => $maintenance['enabled'],
                'message' => $maintenance['message'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function preferences(int $userId): array
    {
        $row = $this->userSettings->findByUserId($userId);

        return [
            'notif_push_enabled'  => $this->flag($row, 'notif_push_enabled'),
            'notif_transactional' => $this->flag($row, 'notif_transactional'),
            'notif_promotional'   => $this->flag($row, 'notif_promotional'),
            'language'            => is_string($row['language'] ?? null) ? $row['language'] : self::DEFAULT_LANGUAGE,
            'theme_mode'          => is_string($row['theme_mode'] ?? null)
                ? $row['theme_mode']
                : UserSettings::THEME_SYSTEM,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appConfig(): array
    {
        $config = [];

        foreach ($this->appSettings->publicSettings() as $row) {
            $setting = AppSetting::fromRow($row);
            $config[$setting->key()] = $setting->typedValue();
        }

        return $config;
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function flag(?array $row, string $column): bool
    {
        if ($row === null || !array_key_exists($column, $row)) {
            return true;
        }

        return (bool) $row[$column];
    }
}
