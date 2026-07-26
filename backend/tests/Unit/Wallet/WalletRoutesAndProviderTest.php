<?php

declare(strict_types=1);

namespace Tests\Unit\Wallet;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\LedgerServiceInterface;
use App\Contracts\WalletRepositoryInterface;
use App\Contracts\WalletServiceInterface;
use App\Contracts\WalletTransactionRepositoryInterface;
use App\Controllers\Wallet\WalletController;
use App\Controllers\Wallet\WalletTransactionController;
use App\Middleware\JwtAuthMiddleware;
use App\Providers\AuthHttpServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\WalletServiceProvider;
use App\Services\LedgerService;
use App\Services\WalletService;
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

final class WalletRoutesAndProviderTest extends TestCase
{
    public function testRoutesRegisterUnderJwtMiddleware(): void
    {
        $router   = new Router(new Container());
        $register = require __DIR__ . '/../../../routes/wallet.php';

        self::assertIsCallable($register);
        $register($router);

        $map = [];
        foreach ($router->routes() as $route) {
            $map[$route->method() . ' ' . $route->path()] = $route->getMiddleware();
        }

        self::assertArrayHasKey('GET /v1/wallet', $map);
        self::assertArrayHasKey('GET /v1/wallet/conversion', $map);
        self::assertArrayHasKey('GET /v1/wallet/transactions', $map);
        self::assertArrayHasKey('GET /v1/wallet/transactions/{uuid}', $map);

        foreach ($map as $middleware) {
            self::assertContains(JwtAuthMiddleware::class, $middleware);
        }
    }

    public function testProviderWiresServicesAndControllers(): void
    {
        $container = new Container();
        $container->instance(Database::class, new Database([]));
        $container->instance(LoggerInterface::class, new NullLogger());
        $container->instance(CacheInterface::class, new FileCache(sys_get_temp_dir() . '/cashnest-wallet-di'));
        $container->instance(\App\Contracts\MailServiceInterface::class, new FakeMailService());
        $container->instance(JwtService::class, new JwtService('wallet-di-secret-0123456789', 'HS256'));
        $container->instance(Config::class, new Config([
            'jwt'    => ['access_ttl' => 1800, 'refresh_ttl' => 2592000],
            'google' => ['client_id' => 'x'],
        ]));

        AuthServiceProvider::register($container);      // UserServiceInterface, etc.
        AuthHttpServiceProvider::register($container);   // TransactionRunnerInterface
        WalletServiceProvider::register($container);

        self::assertInstanceOf(WalletService::class, $container->get(WalletServiceInterface::class));
        self::assertInstanceOf(LedgerService::class, $container->get(LedgerServiceInterface::class));
        self::assertInstanceOf(WalletRepositoryInterface::class, $container->get(WalletRepositoryInterface::class));
        self::assertInstanceOf(
            WalletTransactionRepositoryInterface::class,
            $container->get(WalletTransactionRepositoryInterface::class)
        );
        self::assertInstanceOf(CurrencyRepositoryInterface::class, $container->get(CurrencyRepositoryInterface::class));

        // Router-resolvable controllers.
        self::assertInstanceOf(WalletController::class, $container->get(WalletController::class));
        self::assertInstanceOf(WalletTransactionController::class, $container->get(WalletTransactionController::class));
    }
}
