<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AppVersionRepositoryInterface;
use App\Contracts\AppVersionServiceInterface;
use App\Models\AppVersion;

/**
 * App version service — evaluates the force-update decision per platform.
 *
 * `force_update` is true only when the platform record has force-update enabled
 * AND the caller's build code is below `min_supported_code`. `update_available`
 * reflects whether a newer build exists. The endpoint itself never blocks — it
 * returns the decision so the client can render the gate.
 */
final class AppVersionService implements AppVersionServiceInterface
{
    public function __construct(private AppVersionRepositoryInterface $versions)
    {
    }

    public function versionInfo(string $platform, ?int $clientVersionCode): array
    {
        $platform = in_array($platform, [AppVersion::PLATFORM_ANDROID, AppVersion::PLATFORM_IOS], true)
            ? $platform
            : AppVersion::PLATFORM_ANDROID;

        $row = $this->versions->activeForPlatform($platform);

        if ($row === null) {
            return [
                'platform'            => $platform,
                'latest_version'      => null,
                'latest_version_code' => null,
                'min_supported_code'  => null,
                'force_update'        => false,
                'update_available'    => false,
                'store_url'           => null,
                'changelog'           => null,
            ];
        }

        $version    = AppVersion::fromRow($row);
        $latestCode = $version->latestVersionCode();
        $minCode    = $version->minSupportedCode();

        $forceUpdate     = $version->forceUpdate() && $clientVersionCode !== null && $clientVersionCode < $minCode;
        $updateAvailable = $clientVersionCode !== null && $clientVersionCode < $latestCode;

        return [
            'platform'            => $platform,
            'latest_version'      => $version->get('latest_version'),
            'latest_version_code' => $latestCode,
            'min_supported_code'  => $minCode,
            'force_update'        => $forceUpdate,
            'update_available'    => $updateAvailable,
            'store_url'           => $version->get('store_url'),
            'changelog'           => $version->get('changelog'),
        ];
    }
}
