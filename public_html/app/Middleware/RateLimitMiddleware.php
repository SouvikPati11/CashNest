<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\TooManyRequestsException;
use App\Services\RateLimiter;
use Core\Config;
use Core\Contracts\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Rate-limiting middleware.
 *
 * Applies the default per-user/per-IP throttle from config and attaches the
 * standard X-RateLimit-* headers (API_SPECIFICATION.md §1.12). Feature routes
 * can layer stricter limits later; this provides the baseline ceiling.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private RateLimiter $limiter,
        private Config $config
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $max    = (int) $this->config->get('api.rate_limit', 120);
        $window = (int) $this->config->get('api.rate_window', 60);

        $key = $this->resolveKey($request);

        $allowed   = $this->limiter->attempt($key, $max, $window);
        $remaining = $this->limiter->remaining($key, $max);

        if (!$allowed) {
            throw new TooManyRequestsException($window);
        }

        return $next($request)
            ->withHeader('X-RateLimit-Limit', (string) $max)
            ->withHeader('X-RateLimit-Remaining', (string) $remaining)
            ->withHeader('X-RateLimit-Reset', (string) (time() + $window));
    }

    /**
     * Build the limiter key from the authenticated user (if any) or client IP,
     * scoped by the request path so limits are per-endpoint.
     */
    private function resolveKey(Request $request): string
    {
        $identity = $request->attribute('user_id');

        if (!is_string($identity) && !is_int($identity)) {
            $identity = 'ip:' . $request->ip();
        } else {
            $identity = 'user:' . $identity;
        }

        return $identity . '|' . $request->method() . ' ' . $request->path();
    }
}
