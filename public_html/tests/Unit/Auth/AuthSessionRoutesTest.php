<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Middleware\JwtAuthMiddleware;
use Core\Container;
use Core\Routing\Router;
use PHPUnit\Framework\TestCase;

final class AuthSessionRoutesTest extends TestCase
{
    public function testRegistersGoogleRefreshAndLogoutRoutes(): void
    {
        $router   = new Router(new Container());
        $register = require __DIR__ . '/../../../routes/auth_session.php';

        self::assertIsCallable($register);
        $register($router);

        $map = [];
        foreach ($router->routes() as $route) {
            $map[$route->method() . ' ' . $route->path()] = $route->getMiddleware();
        }

        self::assertArrayHasKey('POST /v1/auth/google', $map);
        self::assertArrayHasKey('POST /v1/auth/refresh', $map);
        self::assertArrayHasKey('POST /v1/auth/logout', $map);

        // Logout is protected; google/refresh are public.
        self::assertContains(JwtAuthMiddleware::class, $map['POST /v1/auth/logout']);
        self::assertNotContains(JwtAuthMiddleware::class, $map['POST /v1/auth/google']);
        self::assertSame([], $map['POST /v1/auth/refresh']);
    }
}
