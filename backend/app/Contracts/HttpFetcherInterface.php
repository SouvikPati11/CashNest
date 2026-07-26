<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Minimal outbound HTTP GET abstraction.
 *
 * Exists so services that need to call an external endpoint (e.g. Google token
 * verification) depend on an interface rather than cURL directly, keeping them
 * unit-testable without network access.
 */
interface HttpFetcherInterface
{
    /**
     * Perform an HTTP GET.
     *
     * @return array{status: int, body: string}
     */
    public function get(string $url): array;
}
