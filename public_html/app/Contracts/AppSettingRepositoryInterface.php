<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * App setting repository contract — reads public `app_settings`.
 */
interface AppSettingRepositoryInterface
{
    /**
     * All settings flagged public (safe to expose to clients).
     *
     * @return array<int, array<string, mixed>>
     */
    public function publicSettings(): array;
}
