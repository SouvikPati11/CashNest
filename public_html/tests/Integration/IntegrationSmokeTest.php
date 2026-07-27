<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\RbacService;
use App\Contracts\CheckinServiceInterface;
use App\Contracts\LedgerServiceInterface;
use App\Contracts\NotificationServiceInterface;
use App\Contracts\OfferwallPostbackServiceInterface;
use App\Contracts\OfferwallServiceInterface;
use App\Contracts\ReferralServiceInterface;
use App\Contracts\SettingsServiceInterface;
use App\Contracts\UserServiceInterface;
use App\Contracts\WithdrawServiceInterface;
use App\Contracts\WithdrawSettlementServiceInterface;
use App\Jobs\JobInterface;
use App\Jobs\SendPushNotificationJob;
use App\Middleware\JwtAuthMiddleware;
use Core\Application;
use Core\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end integration smoke tests.
 *
 * Boots the real application kernel (config, providers, routes) and exercises
 * requests through the full middleware pipeline + router, proving every module
 * is registered and wired together.
 */
final class IntegrationSmokeTest extends TestCase
{
    private static Application $app;

    public static function setUpBeforeClass(): void
    {
        self::$app = Application::boot(dirname(__DIR__, 2));
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     */
    private function request(string $method, string $path, array $query = [], array $headers = []): Request
    {
        return new Request($method, $path, $query, [], $headers, ['REMOTE_ADDR' => '127.0.0.1']);
    }

    public function testEveryModuleServiceResolves(): void
    {
        $container = self::$app->container();

        $services = [
            UserServiceInterface::class,
            JwtAuthMiddleware::class,
            LedgerServiceInterface::class,
            CheckinServiceInterface::class,
            OfferwallServiceInterface::class,
            OfferwallPostbackServiceInterface::class,
            ReferralServiceInterface::class,
            WithdrawServiceInterface::class,
            WithdrawSettlementServiceInterface::class,
            NotificationServiceInterface::class,
            SettingsServiceInterface::class,
            AdminAuthService::class,
            RbacService::class,
        ];

        foreach ($services as $service) {
            self::assertIsObject($container->get($service), $service . ' should resolve');
        }
    }

    public function testHealthEndpointResponds(): void
    {
        $response = self::$app->handle($this->request('GET', '/v1/health'));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('"status"', $response->body());
    }

    public function testApiRootResponds(): void
    {
        $response = self::$app->handle($this->request('GET', '/'));

        self::assertSame(200, $response->status());
    }

    public function testAdminLoginRenders(): void
    {
        $response = self::$app->handle($this->request('GET', '/admin/login'));

        self::assertSame(200, $response->status());
        self::assertStringContainsString('text/html', $response->headers()['Content-Type'] ?? '');
        self::assertStringContainsString('Sign in', $response->body());
    }

    public function testUnknownRouteReturns404(): void
    {
        $response = self::$app->handle($this->request('GET', '/no/such/route'));

        self::assertSame(404, $response->status());
    }

    public function testProtectedApiRouteRequiresAuth(): void
    {
        $response = self::$app->handle($this->request('GET', '/v1/wallet'));

        self::assertSame(401, $response->status());
    }

    public function testGuardedAdminRouteRedirectsToLogin(): void
    {
        $response = self::$app->handle($this->request('GET', '/admin'));

        self::assertSame(302, $response->status());
        self::assertSame('/admin/login', $response->headers()['Location'] ?? '');
    }

    public function testPublicSettingsRoutesAreWired(): void
    {
        // These reach their controller/service (a 500 here would only be the
        // absent test database, never a missing route); 404/405 would mean the
        // route was not registered.
        foreach (['/v1/theme', '/v1/config', '/v1/app/maintenance', '/v1/banners'] as $path) {
            $status = self::$app->handle($this->request('GET', $path))->status();
            self::assertNotSame(404, $status, $path . ' should be routed');
            self::assertNotSame(405, $status, $path . ' should accept GET');
        }
    }

    public function testQueueJobIsWiredAndResolvable(): void
    {
        $job = self::$app->container()->get(SendPushNotificationJob::class);

        self::assertInstanceOf(JobInterface::class, $job);
        self::assertSame('notification.push', SendPushNotificationJob::NAME);
    }
}
