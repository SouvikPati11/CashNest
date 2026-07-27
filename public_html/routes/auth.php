<?php

declare(strict_types=1);

use App\Controllers\Auth\EmailVerificationController;
use App\Controllers\Auth\LoginController;
use App\Controllers\Auth\RegisterController;
use Core\Routing\Router;

/**
 * Authentication routes (email flows) — API_SPECIFICATION.md §2.2–2.4.
 *
 * Returns a callable so the HTTP bootstrap can mount it (alongside the DI
 * providers) without the routes needing container access. Public endpoints;
 * throttling is applied by the global rate-limit middleware.
 */
return static function (Router $router): void {
    $router->group(['prefix' => 'v1/auth/email'], static function (Router $router): void {
        $router->post('/register', [RegisterController::class, 'register']);
        $router->post('/login', [LoginController::class, 'login']);
        $router->post('/verify', [EmailVerificationController::class, 'verify']);
    });
};
