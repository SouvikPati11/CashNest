<?php

/**
 * Notification routes — API_SPECIFICATION.md §2.54–2.57. All JWT-guarded.
 *
 * Notifications are addressed by numeric `id` (the finalised schema has no uuid
 * column). Preference endpoints manage the notification toggles in
 * `user_settings` (the Settings module is out of scope). Campaign broadcast is a
 * domain operation on NotificationCampaignService — no admin HTTP surface here.
 */

declare(strict_types=1);

use App\Controllers\Notification\NotificationController;
use App\Controllers\Notification\NotificationPreferenceController;
use App\Middleware\JwtAuthMiddleware;
use Core\Routing\Router;

return static function (Router $router): void {
    $attributes = ['prefix' => 'v1/notifications', 'middleware' => [JwtAuthMiddleware::class]];

    $router->group($attributes, static function (Router $router): void {
        $router->get('/', [NotificationController::class, 'index']);
        $router->get('/unread-count', [NotificationController::class, 'unreadCount']);
        $router->get('/preferences', [NotificationPreferenceController::class, 'show']);
        $router->put('/preferences', [NotificationPreferenceController::class, 'update']);
        $router->post('/read-all', [NotificationController::class, 'readAll']);
        $router->post('/{id}/read', [NotificationController::class, 'read']);
    });
};
