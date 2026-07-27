<?php

declare(strict_types=1);

use App\Controllers\Reward\CheckinController;
use App\Controllers\Reward\ScratchController;
use App\Controllers\Reward\SpinController;
use App\Controllers\Reward\TaskController;
use App\Middleware\JwtAuthMiddleware;
use Core\Routing\Router;

/**
 * Rewards routes — API_SPECIFICATION.md §2.20–2.34.
 *
 * All endpoints require a valid access token. Resources are addressed by their
 * numeric id (the reward tables define no uuid column in the finalised schema).
 */
return static function (Router $router): void {
    $guarded = ['middleware' => [JwtAuthMiddleware::class]];

    // Daily Check-in.
    $router->group($guarded + ['prefix' => 'v1/checkin'], static function (Router $router): void {
        $router->get('/status', [CheckinController::class, 'status']);
        $router->get('/calendar', [CheckinController::class, 'calendar']);
        $router->post('/claim', [CheckinController::class, 'claim']);
    });

    // Scratch Card.
    $router->group($guarded + ['prefix' => 'v1/scratch'], static function (Router $router): void {
        $router->get('/available', [ScratchController::class, 'available']);
        $router->get('/history', [ScratchController::class, 'history']);
        $router->post('/{id}/reveal', [ScratchController::class, 'reveal']);
        $router->post('/{id}/claim', [ScratchController::class, 'claim']);
    });

    // Spin Wheel.
    $router->group($guarded + ['prefix' => 'v1/spin'], static function (Router $router): void {
        $router->get('/status', [SpinController::class, 'status']);
        $router->get('/history', [SpinController::class, 'history']);
        $router->post('/', [SpinController::class, 'spin']);
    });

    // Tasks.
    $router->group($guarded + ['prefix' => 'v1/tasks'], static function (Router $router): void {
        $router->get('/', [TaskController::class, 'index']);
        $router->get('/history', [TaskController::class, 'history']);
        $router->get('/{id}', [TaskController::class, 'show']);
        $router->post('/{id}/start', [TaskController::class, 'start']);
        $router->post('/{id}/complete', [TaskController::class, 'complete']);
    });
};
