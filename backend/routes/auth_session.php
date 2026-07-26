<?php

declare(strict_types=1);

use App\Controllers\Auth\GoogleLoginController;
use App\Controllers\Auth\LogoutController;
use App\Controllers\Auth\RefreshTokenController;
use App\Middleware\JwtAuthMiddleware;
use Core\Routing\Router;

/**
 * Authentication token/session routes — API_SPECIFICATION.md §2.1, §2.7, §2.8.
 *
 * Google login and refresh are public; logout is protected by the JWT
 * authentication middleware. Returns a callable mounted by the HTTP bootstrap.
 */
return static function (Router $router): void {
    $router->group(['prefix' => 'v1/auth'], static function (Router $router): void {
        $router->post('/google', [GoogleLoginController::class, 'login']);
        $router->post('/refresh', [RefreshTokenController::class, 'refresh']);
        $router->post('/logout', [LogoutController::class, 'logout'])
            ->middleware([JwtAuthMiddleware::class]);
    });
};
