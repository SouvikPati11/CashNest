<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Per-platform app version record (DATABASE_DESIGN.md §L.4).
 *
 * Drives the Force Update decision at launch: a client build code below
 * `min_supported_code` with `force_update` set must be hard-blocked.
 */
final class AppVersion extends BaseModel
{
    public const PLATFORM_ANDROID = 'android';
    public const PLATFORM_IOS     = 'ios';

    protected string $table = 'app_versions';

    /** @var array<int, string> */
    protected array $fillable = [
        'platform',
        'latest_version',
        'latest_version_code',
        'min_supported_code',
        'force_update',
        'changelog',
        'store_url',
        'is_active',
    ];

    /** @var array<string, string> */
    protected array $casts = [
        'id'                  => 'int',
        'latest_version_code' => 'int',
        'min_supported_code'  => 'int',
        'force_update'        => 'bool',
        'is_active'           => 'bool',
    ];

    public function latestVersionCode(): int
    {
        return (int) $this->get('latest_version_code', 0);
    }

    public function minSupportedCode(): int
    {
        return (int) $this->get('min_supported_code', 0);
    }

    public function forceUpdate(): bool
    {
        return (bool) $this->get('force_update', false);
    }
}
