<?php

declare(strict_types=1);

use App\Controllers\Wallet\WalletController;
use App\Controllers\Wallet\WalletTransactionController;
use App\Middleware\JwtAuthMiddleware;
use Core\Routing\Router;

/**
 * Wallet routes — API_SPECIFICATION.md §2.16–2.19.
 *
 * All endpoints require a valid access token (JWT middleware) and are read-only;
 * money movements happen only through the LedgerService, invoked by other modules.
 */
return static function (Router $router): void {
    $attributes = ['prefix' => 'v1/wallet', 'middleware' => [JwtAuthMiddleware::class]];

    $router->group($attributes, static function (Router $router): void {
        $router->get('/', [WalletController::class, 'balance']);
        $router->get('/conversion', [WalletController::class, 'conversion']);
        $router->get('/transactions', [WalletTransactionController::class, 'index']);
        $router->get('/transactions/{uuid}', [WalletTransactionController::class, 'show']);
    });
};
