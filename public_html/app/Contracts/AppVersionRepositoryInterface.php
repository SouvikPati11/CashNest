<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * App version repository contract — reads the active `app_versions` row.
 */
interface AppVersionRepositoryInterface
{
    /**
     * The active version record for a platform.
     *
     * @return array<string, mixed>|null
     */
    public function activeForPlatform(string $platform): ?array;
}
