<?php

declare(strict_types=1);

use App\Controllers\Offerwall\CpaController;
use App\Controllers\Offerwall\OfferwallController;
use App\Controllers\Offerwall\PostbackController;
use App\Middleware\JwtAuthMiddleware;
use Core\Routing\Router;

/**
 * Offerwall & CPA routes — API_SPECIFICATION.md §2.35–2.42.
 *
 * Browse/click endpoints are JWT-guarded; postbacks are public but authenticated
 * by HMAC signature + IP allowlist inside the service. Offers are addressed by
 * numeric id (no uuid column in the finalised schema); providers by slug.
 */
return static function (Router $router): void {
    $guarded = ['middleware' => [JwtAuthMiddleware::class]];

    $router->group($guarded + ['prefix' => 'v1/offerwall'], static function (Router $router): void {
        $router->get('/providers', [OfferwallController::class, 'providers']);
        $router->get('/offers', [OfferwallController::class, 'offers']);
        $router->get('/offers/{id}', [OfferwallController::class, 'show']);
        $router->post('/offers/{id}/click', [OfferwallController::class, 'click']);
    });

    $router->group($guarded + ['prefix' => 'v1/cpa'], static function (Router $router): void {
        $router->get('/offers', [CpaController::class, 'offers']);
        $router->get('/offers/{id}', [CpaController::class, 'show']);
    });

    // Signed server-to-server postbacks (no JWT). Providers may use GET or POST.
    $router->group(['prefix' => 'v1/postback'], static function (Router $router): void {
        $router->post('/offerwall/{provider}', [PostbackController::class, 'offerwall']);
        $router->get('/offerwall/{provider}', [PostbackController::class, 'offerwall']);
        $router->post('/cpa/{provider}', [PostbackController::class, 'cpa']);
        $router->get('/cpa/{provider}', [PostbackController::class, 'cpa']);
    });
};
