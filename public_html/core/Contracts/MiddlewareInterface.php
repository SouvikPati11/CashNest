<?php

declare(strict_types=1);

namespace Core\Contracts;

use Core\Http\Request;
use Core\Http\Response;

/**
 * HTTP middleware contract.
 *
 * A middleware may inspect/modify the request, short-circuit with its own
 * response, or delegate to the next handler by invoking `$next($request)`.
 */
interface MiddlewareInterface
{
    /**
     * @param Request                     $request The incoming request.
     * @param callable(Request): Response $next    The next handler in the chain.
     */
    public function handle(Request $request, callable $next): Response;
}
