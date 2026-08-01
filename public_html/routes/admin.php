<?php

/**
 * Admin panel routes (server-rendered, session + CSRF authenticated).
 *
 * Separate from the JSON API: authentication is session-based (not JWT) and
 * every guarded route runs AdminAuthMiddleware then CsrfMiddleware. RBAC is
 * enforced per-action inside the controllers via BaseAdminController::authorize.
 * Generic content/config sections are served by one metadata-driven route
 * (/admin/r/{resource}). No existing backend module is modified.
 */

declare(strict_types=1);

use App\Admin\Controllers\AuditLogController;
use App\Admin\Controllers\AuthController;
use App\Admin\Controllers\BackupController;
use App\Admin\Controllers\DashboardController;
use App\Admin\Controllers\FraudController;
use App\Admin\Controllers\ReportController;
use App\Admin\Controllers\ResourceController;
use App\Admin\Controllers\RoleController;
use App\Admin\Controllers\SettingsController;
use App\Admin\Controllers\UserController;
use App\Admin\Controllers\WithdrawController;
use App\Admin\Middleware\AdminAuthMiddleware;
use App\Admin\Middleware\CsrfMiddleware;
use Core\Routing\Router;

return static function (Router $router): void {
    // Login screen (pre-auth). The POST is CSRF-protected.
    $router->get('/admin/login', [AuthController::class, 'showLogin']);
    $router->group(['middleware' => [CsrfMiddleware::class]], static function (Router $router): void {
        $router->post('/admin/login', [AuthController::class, 'login']);
    });

    // Everything else requires an authenticated admin session + CSRF on writes.
    $guarded = ['middleware' => [AdminAuthMiddleware::class, CsrfMiddleware::class]];

    $router->group($guarded, static function (Router $router): void {
        $router->post('/admin/logout', [AuthController::class, 'logout']);

        $router->get('/admin', [DashboardController::class, 'index']);

        // Users (export before {id} so it is not captured as an id).
        $router->get('/admin/users/export', [UserController::class, 'export']);
        $router->get('/admin/users', [UserController::class, 'index']);
        $router->get('/admin/users/{id}', [UserController::class, 'show']);
        $router->post('/admin/users/{id}/status', [UserController::class, 'updateStatus']);

        // Withdrawals.
        $router->get('/admin/withdrawals/export', [WithdrawController::class, 'export']);
        $router->get('/admin/withdrawals', [WithdrawController::class, 'index']);
        $router->post('/admin/withdrawals/{id}/approve', [WithdrawController::class, 'approve']);
        $router->post('/admin/withdrawals/{id}/reject', [WithdrawController::class, 'reject']);
        $router->post('/admin/withdrawals/{id}/pay', [WithdrawController::class, 'pay']);

        // Fraud.
        $router->get('/admin/fraud', [FraudController::class, 'index']);
        $router->post('/admin/fraud/{id}/resolve', [FraudController::class, 'resolve']);

        // Governance / system.
        $router->get('/admin/audit-logs', [AuditLogController::class, 'index']);
        $router->get('/admin/reports', [ReportController::class, 'index']);
        $router->get('/admin/reports/{key}/export', [ReportController::class, 'export']);
        $router->get('/admin/roles', [RoleController::class, 'index']);
        $router->get('/admin/settings', [SettingsController::class, 'index']);
        $router->get('/admin/backup', [BackupController::class, 'index']);

        // Generic content/config management browsers (list + generic editor).
        $router->get('/admin/r/{resource}', [ResourceController::class, 'index']);
        $router->get('/admin/r/{resource}/{id}/edit', [ResourceController::class, 'edit']);
        $router->post('/admin/r/{resource}/{id}', [ResourceController::class, 'update']);
    });
};
