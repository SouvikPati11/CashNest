<?php

declare(strict_types=1);

namespace Tests\Unit\Withdraw;

use App\Contracts\WithdrawServiceInterface;
use App\Contracts\WithdrawSettlementServiceInterface;
use App\Controllers\Withdraw\WithdrawController;
use App\Middleware\JwtAuthMiddleware;
use App\Providers\AuthHttpServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\WalletServiceProvider;
use App\Providers\WithdrawServiceProvider;
use App\Services\WithdrawService;
use App\Services\WithdrawSettlementService;
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

final class WithdrawWiringTest extends TestCase
{
    public function testWithdrawRoutesAreGuarded(): void
    {
        $router = new Router(new Container());
        (require __DIR__ . '/../../../routes/withdraw.php')($router);

        $paths = [];
        foreach ($router->routes() as $route) {
            self::assertContains(JwtAuthMiddleware::class, $route->getMiddleware());
            $paths[] = $route->method() . ' ' . $route->path();
        }

        self::assertCount(5, $router->routes());
        self::assertContains('GET /v1/withdraw/methods', $paths);
        self::assertContains('POST /v1/withdraw/request', $paths);
        self::assertContains('GET /v1/withdraw/history', $paths);
        self::assertContains('GET /v1/withdraw/{uuid}', $paths);
        self::assertContains('POST /v1/withdraw/{uuid}/cancel', $paths);
    }

    public function testProviderWiresServicesAndController(): void
    {
        $container = new Container();
        $container->instance(Database::class, new Database([]));
        $container->instance(LoggerInterface::class, new NullLogger());
        $container->instance(CacheInterface::class, new FileCache(sys_get_temp_dir() . '/cashnest-wd-di'));
        $container->instance(\App\Contracts\MailServiceInterface::class, new FakeMailService());
        $container->instance(JwtService::class, new JwtService('wd-di-secret-0123456789', 'HS256'));
        $container->instance(Config::class, new Config([
            'jwt'      => ['access_ttl' => 1800, 'refresh_ttl' => 2592000],
            'google'   => ['client_id' => 'x'],
            'app'      => ['url' => 'https://cashnest.app'],
            'withdraw' => ['kyc_required_threshold_coins' => 50000],
        ]));

        AuthServiceProvider::register($container);
        AuthHttpServiceProvider::register($container);
        WalletServiceProvider::register($container);
        WithdrawServiceProvider::register($container);

        self::assertInstanceOf(WithdrawService::class, $container->get(WithdrawServiceInterface::class));
        self::assertInstanceOf(
            WithdrawSettlementService::class,
            $container->get(WithdrawSettlementServiceInterface::class)
        );
        self::assertInstanceOf(WithdrawController::class, $container->get(WithdrawController::class));
    }
}
