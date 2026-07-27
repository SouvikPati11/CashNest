<?php

declare(strict_types=1);

namespace App\Admin\Middleware;

use App\Admin\Security\CsrfGuard;
use Core\Contracts\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * CSRF verification for state-changing admin requests.
 *
 * On POST/PUT/PATCH/DELETE the submitted `_token` (or `X-CSRF-Token` header) must
 * match the session token. Safe methods pass through untouched.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private CsrfGuard $csrf)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method(), self::PROTECTED_METHODS, true)) {
            $token = $request->input('_token');
            $token = is_string($token) ? $token : $request->header('x-csrf-token');

            if (!$this->csrf->validate($token)) {
                return new Response(
                    419,
                    '<h1>419 - CSRF token mismatch</h1>',
                    ['Content-Type' => 'text/html; charset=UTF-8']
                );
            }
        }

        return $next($request);
    }
}
