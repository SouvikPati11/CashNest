<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * App version service contract — force-update decision per platform.
 */
interface AppVersionServiceInterface
{
    /**
     * Version info + force-update / update-available decision for the caller.
     *
     * @return array<string, mixed>
     */
    public function versionInfo(string $platform, ?int $clientVersionCode): array;
}
