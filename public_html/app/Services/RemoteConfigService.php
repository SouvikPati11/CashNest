<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AppSettingRepositoryInterface;
use App\Contracts\RemoteConfigRepositoryInterface;
use App\Contracts\RemoteConfigServiceInterface;
use App\Models\AppSetting;
use App\Models\RemoteConfig;
use Core\Config;

/**
 * Remote config service (server-driven feature flags).
 *
 * Resolves the effective, typed config map for the current environment from
 * `remote_configs` (the authoritative store), layered over legacy public
 * `app_settings`. Environment-specific rows override the `all` scope. Values are
 * cast per their declared type and served read-only; an ETag supports caching.
 */
final class RemoteConfigService implements RemoteConfigServiceInterface
{
    public function __construct(
        private RemoteConfigRepositoryInterface $remoteConfigs,
        private AppSettingRepositoryInterface $appSettings,
        private Config $config
    ) {
    }

    public function effective(array $keys = []): array
    {
        $environment = (string) $this->config->get('app.env', 'production');
        $environment = in_array($environment, ['production', 'staging'], true) ? $environment : 'production';

        $map = [];

        // Legacy public app_settings first (lowest precedence).
        foreach ($this->appSettings->publicSettings() as $row) {
            $setting = AppSetting::fromRow($row);
            $map[$setting->key()] = $setting->typedValue();
        }

        // Remote configs override; environment-specific rows override `all`
        // because the repository orders `all` before the specific environment.
        foreach ($this->remoteConfigs->activeForEnvironment($environment) as $row) {
            $entry = RemoteConfig::fromRow($row);
            $map[$entry->key()] = $entry->typedValue();
        }

        if ($keys !== []) {
            $map = array_intersect_key($map, array_flip($keys));
        }

        return [$map, $this->etag($map)];
    }

    /**
     * @param array<string, mixed> $map
     */
    private function etag(array $map): string
    {
        return 'cfg_' . substr(hash('sha256', (string) json_encode($map)), 0, 12);
    }
}
