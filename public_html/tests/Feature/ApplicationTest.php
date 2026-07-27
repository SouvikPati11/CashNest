<?php

declare(strict_types=1);

namespace Tests\Feature;

use Core\Application;
use Core\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end style tests that boot the real Application kernel and dispatch
 * in-memory requests through the full middleware + router pipeline.
 */
final class ApplicationTest extends TestCase
{
    private function app(): Application
    {
        // tests/Feature -> backend root is two levels up.
        return Application::boot(dirname(__DIR__, 2));
    }

    public function testApiRootReturnsInfo(): void
    {
        $response = $this->app()->handle(new Request('GET', '/'));
        $payload  = json_decode($response->body(), true);

        self::assertSame(200, $response->status());
        self::assertSame('success', $payload['status']);
        self::assertSame('online', $payload['data']['status']);
    }

    public function testHealthEndpointReturnsHealthy(): void
    {
        // DB is unconfigured in testing, so the DB check is "skipped" => healthy.
        $response = $this->app()->handle(new Request('GET', '/v1/health'));
        $payload  = json_decode($response->body(), true);

        self::assertSame(200, $response->status());
        self::assertSame('healthy', $payload['data']['status']);
        self::assertSame('skipped', $payload['data']['checks']['database']);
    }

    public function testUnknownRouteReturns404Envelope(): void
    {
        $response = $this->app()->handle(new Request('GET', '/v1/does-not-exist'));
        $payload  = json_decode($response->body(), true);

        self::assertSame(404, $response->status());
        self::assertSame('error', $payload['status']);
        self::assertSame('NOT_FOUND', $payload['errors'][0]['code']);
    }

    public function testCorsHeadersPresent(): void
    {
        $response = $this->app()->handle(new Request('GET', '/v1/health'));

        self::assertArrayHasKey('Access-Control-Allow-Origin', $response->headers());
    }

    public function testRateLimitHeadersPresent(): void
    {
        $response = $this->app()->handle(new Request('GET', '/v1/health'));

        self::assertArrayHasKey('X-Ratelimit-Limit', $response->headers());
    }
}
