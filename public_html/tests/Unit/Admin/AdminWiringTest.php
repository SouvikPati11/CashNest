<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Controllers\AuditLogController;
use App\Admin\Controllers\AuthController;
use App\Admin\Controllers\BackupController;
use App\Admin\Controllers\DashboardController;
use App\Admin\Controllers\FraudController;
use App\Admin\Controllers\ReportController;
use App\Admin\Controllers\ResourceController;
use App\Admin\Controllers\RoleController;
use App\Admin\Controllers\SettingsController;
use App\Admin\Controllers\UserController;
use App\Admin\Controllers\WithdrawController;
use App\Admin\Middleware\AdminAuthMiddleware;
use App\Admin\Middleware\CsrfMiddleware;
use App\Admin\Services\AdminAuthService;
use App\Admin\Services\RbacService;
use App\Providers\AdminServiceProvider;
use App\Providers\AuthHttpServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\SettingsPlatformServiceProvider;
use App\Providers\WalletServiceProvider;
use App\Providers\WithdrawServiceProvider;
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

final class AdminWiringTest extends TestCase
{
    /**
     * @return array<string, array<int, string>>
     */
    private function routeMap(): array
    {
        $router = new Router(new Container());
        (require __DIR__ . '/../../../routes/admin.php')($router);

        $map = [];
        foreach ($router->routes() as $route) {
            $map[$route->method() . ' ' . $route->path()] = $route->getMiddleware();
        }

        return $map;
    }

    public function testRoutesGuardedExceptLogin(): void
    {
        $map = $this->routeMap();

        self::assertCount(22, $map);
        self::assertSame([], $map['GET /admin/login']);
        self::assertContains(CsrfMiddleware::class, $map['POST /admin/login']);
        self::assertNotContains(AdminAuthMiddleware::class, $map['POST /admin/login']);

        self::assertContains(AdminAuthMiddleware::class, $map['GET /admin']);
        self::assertContains(CsrfMiddleware::class, $map['GET /admin']);
        self::assertContains(AdminAuthMiddleware::class, $map['POST /admin/withdrawals/{id}/approve']);
        self::assertContains(AdminAuthMiddleware::class, $map['GET /admin/r/{resource}']);
    }

    public function testProviderWiresServicesAndControllers(): void
    {
        $container = new Container();
        $container->instance(Database::class, new Database([]));
        $container->instance(LoggerInterface::class, new NullLogger());
        $container->instance(CacheInterface::class, new FileCache(sys_get_temp_dir() . '/cashnest-admin-di'));
        $container->instance(\App\Contracts\MailServiceInterface::class, new FakeMailService());
        $container->instance(JwtService::class, new JwtService('admin-di-secret-0123456789', 'HS256'));
        $container->instance(Config::class, new Config([
            'jwt'      => ['access_ttl' => 1800, 'refresh_ttl' => 2592000],
            'google'   => ['client_id' => 'x'],
            'app'      => ['url' => 'https://cashnest.app', 'env' => 'production'],
            'admin'    => ['session_key' => 'test_admin', 'navigation' => []],
            'withdraw' => ['kyc_required_threshold_coins' => 50000],
        ]));

        AuthServiceProvider::register($container);
        AuthHttpServiceProvider::register($container);
        WalletServiceProvider::register($container);
        WithdrawServiceProvider::register($container);
        SettingsPlatformServiceProvider::register($container);
        AdminServiceProvider::register($container);

        self::assertInstanceOf(AdminAuthService::class, $container->get(AdminAuthService::class));
        self::assertInstanceOf(RbacService::class, $container->get(RbacService::class));

        $controllers = [
            AuthController::class,
            DashboardController::class,
            UserController::class,
            WithdrawController::class,
            FraudController::class,
            AuditLogController::class,
            ReportController::class,
            RoleController::class,
            ResourceController::class,
            SettingsController::class,
            BackupController::class,
        ];
        foreach ($controllers as $controller) {
            self::assertIsObject($container->get($controller));
        }
    }
}
