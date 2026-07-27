<?php

declare(strict_types=1);

namespace Tests\Unit\Offerwall;

use App\Contracts\OfferwallPostbackServiceInterface;
use App\Contracts\OfferwallServiceInterface;
use App\Contracts\ReferralCommissionServiceInterface;
use App\Contracts\ReferralServiceInterface;
use App\Controllers\Offerwall\CpaController;
use App\Controllers\Offerwall\OfferwallController;
use App\Controllers\Offerwall\PostbackController;
use App\Controllers\Referral\ReferralController;
use App\Middleware\JwtAuthMiddleware;
use App\Providers\AuthHttpServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\OfferwallServiceProvider;
use App\Providers\ReferralServiceProvider;
use App\Providers\WalletServiceProvider;
use App\Services\OfferwallPostbackService;
use App\Services\OfferwallService;
use App\Services\ReferralService;
use Core\Cache\FileCache;
use Core\Config;
use Core\Container;
use Core\Contracts\CacheInterface;
use Core\Contracts\LoggerInterface;
use Core\Database\Database;
use Core\Routing\Router;
use Core\Security\JwtService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeMailService;
use Tests\Support\NullLogger;

final class OfferwallReferralWiringTest extends TestCase
{
    public function testOfferwallRoutes(): void
    {
        $router = new Router(new Container());
        (require __DIR__ . '/../../../routes/offerwall.php')($router);

        $map = [];
        foreach ($router->routes() as $route) {
            $map[$route->method() . ' ' . $route->path()] = $route->getMiddleware();
        }

        self::assertContains(JwtAuthMiddleware::class, $map['GET /v1/offerwall/offers']);
        self::assertContains(JwtAuthMiddleware::class, $map['POST /v1/offerwall/offers/{id}/click']);
        self::assertContains(JwtAuthMiddleware::class, $map['GET /v1/cpa/offers']);

        // Postbacks must NOT be JWT-guarded (signed instead).
        self::assertSame([], $map['POST /v1/postback/offerwall/{provider}']);
        self::assertSame([], $map['POST /v1/postback/cpa/{provider}']);
    }

    public function testReferralRoutesGuarded(): void
    {
        $router = new Router(new Container());
        (require __DIR__ . '/../../../routes/referral.php')($router);

        foreach ($router->routes() as $route) {
            self::assertContains(JwtAuthMiddleware::class, $route->getMiddleware());
        }
        self::assertCount(4, $router->routes());
    }

    public function testProvidersWireServicesAndControllers(): void
    {
        $container = new Container();
        $container->instance(Database::class, new Database([]));
        $container->instance(LoggerInterface::class, new NullLogger());
        $container->instance(CacheInterface::class, new FileCache(sys_get_temp_dir() . '/cashnest-ow-di'));
        $container->instance(\App\Contracts\MailServiceInterface::class, new FakeMailService());
        $container->instance(JwtService::class, new JwtService('ow-di-secret-0123456789', 'HS256'));
        $container->instance(Config::class, new Config([
            'jwt'    => ['access_ttl' => 1800, 'refresh_ttl' => 2592000],
            'google' => ['client_id' => 'x'],
            'app'    => ['url' => 'https://cashnest.app'],
        ]));

        AuthServiceProvider::register($container);
        AuthHttpServiceProvider::register($container);
        WalletServiceProvider::register($container);
        ReferralServiceProvider::register($container);
        OfferwallServiceProvider::register($container);

        self::assertInstanceOf(OfferwallService::class, $container->get(OfferwallServiceInterface::class));
        self::assertInstanceOf(
            OfferwallPostbackService::class,
            $container->get(OfferwallPostbackServiceInterface::class)
        );
        self::assertInstanceOf(ReferralService::class, $container->get(ReferralServiceInterface::class));
        self::assertInstanceOf(
            ReferralCommissionServiceInterface::class,
            $container->get(ReferralCommissionServiceInterface::class)
        );

        // Router-resolvable controllers.
        self::assertInstanceOf(OfferwallController::class, $container->get(OfferwallController::class));
        self::assertInstanceOf(CpaController::class, $container->get(CpaController::class));
        self::assertInstanceOf(PostbackController::class, $container->get(PostbackController::class));
        self::assertInstanceOf(ReferralController::class, $container->get(ReferralController::class));
    }
}
