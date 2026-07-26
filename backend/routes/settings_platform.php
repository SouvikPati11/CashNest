<?php

/**
 * Settings Platform routes — API_SPECIFICATION.md §2.58–2.80.
 *
 * User-scoped preferences, announcements, and home layout are JWT-guarded; the
 * server-driven content/config endpoints (config, theme, banners, cms, app
 * version, maintenance) are public. Resources with no uuid column in the
 * finalised schema are addressed by numeric id. All endpoints are admin-managed
 * later; no admin HTTP surface is built here.
 */

declare(strict_types=1);

use App\Controllers\Settings\AnnouncementController;
use App\Controllers\Settings\AppController;
use App\Controllers\Settings\BannerController;
use App\Controllers\Settings\CmsController;
use App\Controllers\Settings\HomeLayoutController;
use App\Controllers\Settings\RemoteConfigController;
use App\Controllers\Settings\SettingsController;
use App\Controllers\Settings\ThemeController;
use App\Middleware\JwtAuthMiddleware;
use Core\Routing\Router;

return static function (Router $router): void {
    $guarded = ['middleware' => [JwtAuthMiddleware::class]];

    // User settings + preferences (JWT).
    $router->group($guarded + ['prefix' => 'v1/settings'], static function (Router $router): void {
        $router->get('/', [SettingsController::class, 'index']);
        $router->put('/', [SettingsController::class, 'update']);
    });
    $router->get('/v1/settings/app', [SettingsController::class, 'app']);

    // Announcements + dismissal (JWT).
    $router->group($guarded + ['prefix' => 'v1/announcements'], static function (Router $router): void {
        $router->get('/', [AnnouncementController::class, 'index']);
        $router->post('/{id}/seen', [AnnouncementController::class, 'seen']);
    });

    // Dynamic home layout (JWT).
    $router->group($guarded + ['prefix' => 'v1/home'], static function (Router $router): void {
        $router->get('/layout', [HomeLayoutController::class, 'index']);
    });

    // Public, server-driven content/config.
    $router->get('/v1/config', [RemoteConfigController::class, 'index']);
    $router->get('/v1/theme', [ThemeController::class, 'index']);
    $router->get('/v1/banners', [BannerController::class, 'index']);
    $router->get('/v1/cms/faq', [CmsController::class, 'faq']);
    $router->get('/v1/cms/{slug}', [CmsController::class, 'page']);
    $router->get('/v1/app/version', [AppController::class, 'version']);
    $router->get('/v1/app/maintenance', [AppController::class, 'maintenance']);
};
