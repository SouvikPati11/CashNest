<?php

/**
 * Withdraw routes — API_SPECIFICATION.md §2.49–2.53. All JWT-guarded.
 *
 * Requests are addressed by their public `uuid`. Admin approval/settlement is
 * exposed as domain operations on WithdrawSettlementService (no admin HTTP
 * surface is built in this module).
 */

declare(strict_types=1);

use App\Controllers\Withdraw\WithdrawController;
use App\Middleware\JwtAuthMiddleware;
use Core\Routing\Router;

return static function (Router $router): void {
    $attributes = ['prefix' => 'v1/withdraw', 'middleware' => [JwtAuthMiddleware::class]];

    $router->group($attributes, static function (Router $router): void {
        $router->get('/methods', [WithdrawController::class, 'methods']);
        $router->post('/request', [WithdrawController::class, 'request']);
        $router->get('/history', [WithdrawController::class, 'history']);
        $router->get('/{uuid}', [WithdrawController::class, 'show']);
        $router->post('/{uuid}/cancel', [WithdrawController::class, 'cancel']);
    });
};
