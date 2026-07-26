<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Contracts\AnnouncementServiceInterface;
use App\Contracts\AppVersionServiceInterface;
use App\Contracts\BannerServiceInterface;
use App\Contracts\CmsServiceInterface;
use App\Contracts\HomeLayoutServiceInterface;
use App\Contracts\MaintenanceServiceInterface;
use App\Contracts\RemoteConfigServiceInterface;
use App\Contracts\SettingsServiceInterface;
use App\Contracts\ThemeServiceInterface;
use App\Controllers\Settings\AnnouncementController;
use App\Controllers\Settings\AppController;
use App\Controllers\Settings\BannerController;
use App\Controllers\Settings\CmsController;
use App\Controllers\Settings\HomeLayoutController;
use App\Controllers\Settings\RemoteConfigController;
use App\Controllers\Settings\SettingsController;
use App\Controllers\Settings\ThemeController;
use App\Middleware\JwtAuthMiddleware;
use App\Providers\SettingsPlatformServiceProvider;
use App\Services\SettingsService;
use App\Services\ThemeService;
use Core\Config;
use Core\Container;
use Core\Database\Database;
use Core\Routing\Router;
use PHPUnit\Framework\TestCase;

final class SettingsPlatformWiringTest extends TestCase
{
    /**
     * @return array<string, array<int, string>>
     */
    private function routeMap(): array
    {
        $router = new Router(new Container());
        (require __DIR__ . '/../../../routes/settings_platform.php')($router);

        $map = [];
        foreach ($router->routes() as $route) {
            $map[$route->method() . ' ' . $route->path()] = $route->getMiddleware();
        }

        return $map;
    }

    public function testUserScopedRoutesAreGuarded(): void
    {
        $map = $this->routeMap();

        self::assertCount(13, $map);
        self::assertContains(JwtAuthMiddleware::class, $map['GET /v1/settings']);
        self::assertContains(JwtAuthMiddleware::class, $map['PUT /v1/settings']);
        self::assertContains(JwtAuthMiddleware::class, $map['GET /v1/announcements']);
        self::assertContains(JwtAuthMiddleware::class, $map['POST /v1/announcements/{id}/seen']);
        self::assertContains(JwtAuthMiddleware::class, $map['GET /v1/home/layout']);
    }

    public function testPublicContentRoutesAreUnguarded(): void
    {
        $map = $this->routeMap();

        self::assertSame([], $map['GET /v1/config']);
        self::assertSame([], $map['GET /v1/theme']);
        self::assertSame([], $map['GET /v1/banners']);
        self::assertSame([], $map['GET /v1/cms/{slug}']);
        self::assertSame([], $map['GET /v1/cms/faq']);
        self::assertSame([], $map['GET /v1/app/version']);
        self::assertSame([], $map['GET /v1/app/maintenance']);
        self::assertSame([], $map['GET /v1/settings/app']);
    }

    public function testProviderWiresServicesAndControllers(): void
    {
        $container = new Container();
        $container->instance(Database::class, new Database([]));
        $container->instance(Config::class, new Config(['app' => ['env' => 'production']]));

        SettingsPlatformServiceProvider::register($container);

        self::assertInstanceOf(SettingsService::class, $container->get(SettingsServiceInterface::class));
        self::assertInstanceOf(ThemeService::class, $container->get(ThemeServiceInterface::class));

        $services = [
            RemoteConfigServiceInterface::class,
            BannerServiceInterface::class,
            AnnouncementServiceInterface::class,
            AppVersionServiceInterface::class,
            MaintenanceServiceInterface::class,
            CmsServiceInterface::class,
            HomeLayoutServiceInterface::class,
        ];
        foreach ($services as $service) {
            self::assertIsObject($container->get($service));
        }

        $controllers = [
            SettingsController::class,
            RemoteConfigController::class,
            ThemeController::class,
            BannerController::class,
            AnnouncementController::class,
            AppController::class,
            CmsController::class,
            HomeLayoutController::class,
        ];
        foreach ($controllers as $controller) {
            self::assertIsObject($container->get($controller));
        }
    }
}
