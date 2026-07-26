<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use Core\Container;
use Core\Routing\Router;
use PHPUnit\Framework\TestCase;

final class AuthRoutesTest extends TestCase
{
    public function testAuthRoutesFileRegistersEmailEndpoints(): void
    {
        $router   = new Router(new Container());
        $register = require __DIR__ . '/../../../routes/auth.php';

        self::assertIsCallable($register);
        $register($router);

        $registered = [];
        foreach ($router->routes() as $route) {
            $registered[] = $route->method() . ' ' . $route->path();
        }

        self::assertContains('POST /v1/auth/email/register', $registered);
        self::assertContains('POST /v1/auth/email/login', $registered);
        self::assertContains('POST /v1/auth/email/verify', $registered);
        self::assertCount(3, $registered);
    }
}
