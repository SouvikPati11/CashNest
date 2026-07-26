<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Remote config repository contract — reads active `remote_configs`.
 */
interface RemoteConfigRepositoryInterface
{
    /**
     * Active config rows for the given environment (plus `all`).
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeForEnvironment(string $environment): array;
}
