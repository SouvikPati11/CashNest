<?php

declare(strict_types=1);

use App\Controllers\Referral\ReferralController;
use App\Middleware\JwtAuthMiddleware;
use Core\Routing\Router;

/**
 * Referral routes — API_SPECIFICATION.md §2.43–2.46. All JWT-guarded.
 */
return static function (Router $router): void {
    $attributes = ['prefix' => 'v1/referral', 'middleware' => [JwtAuthMiddleware::class]];

    $router->group($attributes, static function (Router $router): void {
        $router->get('/', [ReferralController::class, 'index']);
        $router->get('/list', [ReferralController::class, 'list']);
        $router->get('/earnings', [ReferralController::class, 'earnings']);
        $router->post('/apply', [ReferralController::class, 'apply']);
    });
};
