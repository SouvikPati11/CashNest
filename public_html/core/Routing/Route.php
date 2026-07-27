<?php

declare(strict_types=1);

namespace Core\Routing;

use Core\Contracts\MiddlewareInterface;

/**
 * A single registered route.
 *
 * Holds the HTTP method, a compiled path pattern, the handler (either a
 * `[Controller::class, 'method']` pair or a callable), and any route-specific
 * middleware.
 */
final class Route
{
    private string $regex;

    /** @var array<int, string> */
    private array $paramNames = [];

    /** @var array<int, class-string<MiddlewareInterface>> */
    private array $middleware = [];

    /**
     * @param string                        $method  HTTP verb.
     * @param string                        $path    Path template, e.g. /v1/users/{uuid}.
     * @param array{0: class-string, 1: string}|callable $handler Route handler.
     */
    public function __construct(
        private string $method,
        private string $path,
        private mixed $handler
    ) {
        $this->compile($path);
    }

    /**
     * Attach middleware (by class name) to this route.
     *
     * @param array<int, class-string<MiddlewareInterface>> $middleware
     */
    public function middleware(array $middleware): self
    {
        $this->middleware = array_values(array_unique([...$this->middleware, ...$middleware]));

        return $this;
    }

    /**
     * Attempt to match a path, returning captured params or null.
     *
     * @return array<string, string>|null
     */
    public function match(string $method, string $path): ?array
    {
        if (strcasecmp($method, $this->method) !== 0) {
            return null;
        }

        if (preg_match($this->regex, $path, $matches) !== 1) {
            return null;
        }

        $params = [];

        foreach ($this->paramNames as $name) {
            if (isset($matches[$name])) {
                $params[$name] = $matches[$name];
            }
        }

        return $params;
    }

    /**
     * Whether this route's path pattern matches, ignoring the HTTP method.
     * Used to distinguish 404 (no path) from 405 (path, wrong method).
     */
    public function matchesPath(string $path): bool
    {
        return preg_match($this->regex, $path) === 1;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array{0: class-string, 1: string}|callable
     */
    public function handler(): mixed
    {
        return $this->handler;
    }

    /**
     * @return array<int, class-string<MiddlewareInterface>>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Compile a path template into a regex with named capture groups.
     */
    private function compile(string $path): void
    {
        $normalized = '/' . trim($path, '/');

        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function (array $m): string {
                $this->paramNames[] = $m[1];
                return '(?P<' . $m[1] . '>[^/]+)';
            },
            $normalized
        );

        $this->regex = '#^' . $regex . '$#';
    }
}
