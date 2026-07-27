<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Remote config service contract (server-driven feature flags).
 */
interface RemoteConfigServiceInterface
{
    /**
     * The effective, typed config map for the client, optionally filtered to a
     * subset of keys. The second return element is a stable ETag for caching.
     *
     * @param array<int, string> $keys
     * @return array{0: array<string, mixed>, 1: string}
     */
    public function effective(array $keys = []): array;
}
