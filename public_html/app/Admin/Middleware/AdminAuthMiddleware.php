<?php

declare(strict_types=1);

namespace App\Admin\Middleware;

use App\Admin\Services\AdminAuthService;
use App\Models\Admin;
use Core\Contracts\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Admin session authentication guard.
 *
 * Ensures an active admin is logged in before reaching guarded admin routes,
 * attaching the identity to the request. Unauthenticated callers are redirected
 * to the login screen (browser flow) rather than receiving a JSON error.
 */
final class AdminAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private AdminAuthService $auth)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $current = $this->auth->current();

        if ($current === null) {
            return new Response(302, '', ['Location' => '/admin/login']);
        }

        $admin = Admin::fromRow($current);

        if (!$admin->isActive()) {
            $this->auth->logout($request->ip());

            return new Response(302, '', ['Location' => '/admin/login']);
        }

        $request->setAttribute('admin', $current);
        $request->setAttribute('admin_id', $admin->id());
        $request->setAttribute('admin_role_id', $admin->roleId());

        return $next($request);
    }
}
