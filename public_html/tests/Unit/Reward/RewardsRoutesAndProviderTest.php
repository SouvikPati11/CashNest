<?php

declare(strict_types=1);

namespace Tests\Unit\Reward;

use App\Contracts\CheckinServiceInterface;
use App\Contracts\ScratchServiceInterface;
use App\Contracts\SpinServiceInterface;
use App\Contracts\TaskServiceInterface;
use App\Controllers\Reward\CheckinController;
use App\Controllers\Reward\ScratchController;
use App\Controllers\Reward\SpinController;
use App\Controllers\Reward\TaskController;
use App\Middleware\JwtAuthMiddleware;
use App\Providers\AuthHttpServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\RewardsServiceProvider;
use App\Providers\WalletServiceProvider;
use App\Services\CheckinService;
use App\Services\ScratchService;
use App\Services\SpinService;
use App\Services\TaskService;
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

final class RewardsRoutesAndProviderTest extends TestCase
{
    public function testAllRewardRoutesAreGuarded(): void
    {
        $router   = new Router(new Container());
        $register = require __DIR__ . '/../../../routes/rewards.php';

        self::assertIsCallable($register);
        $register($router);

        $routes = $router->routes();
        self::assertCount(15, $routes);

        foreach ($routes as $route) {
            self::assertContains(
                JwtAuthMiddleware::class,
                $route->getMiddleware(),
                $route->method() . ' ' . $route->path() . ' must be JWT-guarded'
            );
        }
    }

    public function testProviderWiresServicesAndControllers(): void
    {
        $container = new Container();
        $container->instance(Database::class, new Database([]));
        $container->instance(LoggerInterface::class, new NullLogger());
        $container->instance(CacheInterface::class, new FileCache(sys_get_temp_dir() . '/cashnest-rewards-di'));
        $container->instance(\App\Contracts\MailServiceInterface::class, new FakeMailService());
        $container->instance(JwtService::class, new JwtService('rewards-di-secret-0123456789', 'HS256'));
        $container->instance(Config::class, new Config([
            'jwt'     => ['access_ttl' => 1800, 'refresh_ttl' => 2592000],
            'google'  => ['client_id' => 'x'],
            'rewards' => ['spin' => ['daily_limit' => 3]],
        ]));

        AuthServiceProvider::register($container);
        AuthHttpServiceProvider::register($container);
        WalletServiceProvider::register($container);
        RewardsServiceProvider::register($container);

        self::assertInstanceOf(CheckinService::class, $container->get(CheckinServiceInterface::class));
        self::assertInstanceOf(ScratchService::class, $container->get(ScratchServiceInterface::class));
        self::assertInstanceOf(SpinService::class, $container->get(SpinServiceInterface::class));
        self::assertInstanceOf(TaskService::class, $container->get(TaskServiceInterface::class));

        // Router-resolvable controllers.
        self::assertInstanceOf(CheckinController::class, $container->get(CheckinController::class));
        self::assertInstanceOf(ScratchController::class, $container->get(ScratchController::class));
        self::assertInstanceOf(SpinController::class, $container->get(SpinController::class));
        self::assertInstanceOf(TaskController::class, $container->get(TaskController::class));
    }
}
