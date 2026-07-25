<?php

declare(strict_types=1);

use App\Controllers\ApiInfoController;
use App\Controllers\HealthController;
use Core\Routing\Router;

/**
 * API routes.
 *
 * Only foundation routes exist today: API info, health check, and a versioned
 * group placeholder. Feature module routes (auth, wallet, rewards, ...) will be
 * registered here later. This file returns a callable so the Application can
 * inject the Router.
 */
return static function (Router $router): void {
    // API discovery root.
    $router->get('/', [ApiInfoController::class, 'index']);

    // Versioned API group.
    $router->group(['prefix' => 'v1'], static function (Router $router): void {
        // Version root / discovery.
        $router->get('/', [ApiInfoController::class, 'index']);

        // Health check (liveness/readiness).
        $router->get('/health', [HealthController::class, 'index']);

        /*
        |------------------------------------------------------------------
        | Feature module routes are registered below in later milestones.
        | Do NOT add authentication or business routes at the foundation
        | stage.
        |------------------------------------------------------------------
        */
    });
};
