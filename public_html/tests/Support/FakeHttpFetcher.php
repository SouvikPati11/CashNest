<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\HttpFetcherInterface;

/**
 * HTTP fetcher double returning a canned response.
 */
final class FakeHttpFetcher implements HttpFetcherInterface
{
    public string $lastUrl = '';

    public function __construct(
        private int $status = 200,
        private string $body = '{}'
    ) {
    }

    public function set(int $status, string $body): void
    {
        $this->status = $status;
        $this->body   = $body;
    }

    public function get(string $url): array
    {
        $this->lastUrl = $url;

        return ['status' => $this->status, 'body' => $this->body];
    }
}
