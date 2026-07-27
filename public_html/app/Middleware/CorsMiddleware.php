<?php

declare(strict_types=1);

namespace App\Middleware;

use Core\Config;
use Core\Contracts\MiddlewareInterface;
use Core\Http\Request;
use Core\Http\Response;

/**
 * CORS middleware.
 *
 * Applies the configured Cross-Origin Resource Sharing headers and short-circuits
 * pre-flight (OPTIONS) requests with a 204. Origins are validated against the
 * allowlist from config so credentials are never exposed to arbitrary sites.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(private Config $config)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $allowedOrigins = (array) $this->config->get('cors.allowed_origins', ['*']);
        $origin         = $request->header('origin', '') ?? '';

        $allowOrigin = $this->resolveAllowedOrigin($origin, $allowedOrigins);

        if ($request->method() === 'OPTIONS') {
            return $this->applyHeaders(Response::noContent(204), $allowOrigin);
        }

        return $this->applyHeaders($next($request), $allowOrigin);
    }

    /**
     * Determine the Access-Control-Allow-Origin value to return.
     *
     * @param array<int, string> $allowed
     */
    private function resolveAllowedOrigin(string $origin, array $allowed): string
    {
        if (in_array('*', $allowed, true)) {
            return '*';
        }

        if ($origin !== '' && in_array($origin, $allowed, true)) {
            return $origin;
        }

        // Origin not on the allowlist: emit no Allow-Origin header at all.
        return '';
    }

    private function applyHeaders(Response $response, string $allowOrigin): Response
    {
        $methods = implode(', ', (array) $this->config->get('cors.allowed_methods', ['GET', 'POST']));
        $headers = implode(', ', (array) $this->config->get('cors.allowed_headers', ['Content-Type']));
        $maxAge  = (string) $this->config->get('cors.max_age', 86400);

        $response = $response
            ->withHeader('Access-Control-Allow-Methods', $methods)
            ->withHeader('Access-Control-Allow-Headers', $headers)
            ->withHeader('Access-Control-Max-Age', $maxAge)
            ->withHeader('Vary', 'Origin');

        if ($allowOrigin !== '') {
            $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin);
        }

        return $response;
    }
}
