<?php

declare(strict_types=1);

namespace Core\Routing;

use App\Exceptions\MethodNotAllowedException;
use App\Exceptions\NotFoundException;
use Core\Contracts\ContainerInterface;
use Core\Contracts\MiddlewareInterface;
use Core\Http\Pipeline;
use Core\Http\Request;
use Core\Http\Response;

/**
 * HTTP router.
 *
 * Registers routes (optionally grouped under a shared prefix and middleware),
 * matches an incoming request, runs the route's middleware pipeline, and invokes
 * the resolved controller action.
 */
final class Router
{
    /** @var array<int, Route> */
    private array $routes = [];

    /** @var array<int, string> Path prefixes for the current group scope. */
    private array $groupPrefix = [];

    /** @var array<int, class-string<MiddlewareInterface>> Middleware for the current group scope. */
    private array $groupMiddleware = [];

    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * Register a GET route.
     *
     * @param array{0: class-string, 1: string}|callable $handler
     */
    public function get(string $path, mixed $handler): Route
    {
        return $this->add('GET', $path, $handler);
    }

    /**
     * @param array{0: class-string, 1: string}|callable $handler
     */
    public function post(string $path, mixed $handler): Route
    {
        return $this->add('POST', $path, $handler);
    }

    /**
     * @param array{0: class-string, 1: string}|callable $handler
     */
    public function put(string $path, mixed $handler): Route
    {
        return $this->add('PUT', $path, $handler);
    }

    /**
     * @param array{0: class-string, 1: string}|callable $handler
     */
    public function patch(string $path, mixed $handler): Route
    {
        return $this->add('PATCH', $path, $handler);
    }

    /**
     * @param array{0: class-string, 1: string}|callable $handler
     */
    public function delete(string $path, mixed $handler): Route
    {
        return $this->add('DELETE', $path, $handler);
    }

    /**
     * Group routes under a shared prefix and/or middleware.
     *
     * @param array{prefix?: string, middleware?: array<int, class-string<MiddlewareInterface>>} $attributes
     * @param callable(Router): void $callback
     */
    public function group(array $attributes, callable $callback): void
    {
        if (isset($attributes['prefix'])) {
            $this->groupPrefix[] = trim($attributes['prefix'], '/');
        }

        $addedMiddleware = $attributes['middleware'] ?? [];
        if ($addedMiddleware !== []) {
            $this->groupMiddleware = [...$this->groupMiddleware, ...$addedMiddleware];
        }

        $callback($this);

        // Restore the outer scope.
        if (isset($attributes['prefix'])) {
            array_pop($this->groupPrefix);
        }

        if ($addedMiddleware !== []) {
            $this->groupMiddleware = array_slice(
                $this->groupMiddleware,
                0,
                count($this->groupMiddleware) - count($addedMiddleware)
            );
        }
    }

    /**
     * Register a route in the current group scope.
     *
     * @param array{0: class-string, 1: string}|callable $handler
     */
    public function add(string $method, string $path, mixed $handler): Route
    {
        $prefix = implode('/', array_filter($this->groupPrefix));
        $full   = '/' . trim($prefix . '/' . trim($path, '/'), '/');

        $route = new Route($method, $full === '//' ? '/' : $full, $handler);

        if ($this->groupMiddleware !== []) {
            $route->middleware($this->groupMiddleware);
        }

        $this->routes[] = $route;

        return $route;
    }

    /**
     * Match and dispatch a request to its route.
     *
     * @throws NotFoundException When no route matches.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = $request->path();

        // Primary pass: exact method + path match.
        foreach ($this->routes as $route) {
            $params = $route->match($method, $path);

            if ($params !== null) {
                $request->setRouteParams($params);
                return $this->runRoute($route, $request);
            }
        }

        // HEAD falls back to the matching GET route, with the body stripped.
        if ($method === 'HEAD') {
            foreach ($this->routes as $route) {
                $params = $route->match('GET', $path);

                if ($params !== null) {
                    $request->setRouteParams($params);
                    $response = $this->runRoute($route, $request);
                    return new Response($response->status(), '', $response->headers());
                }
            }
        }

        // Path exists but no method matched -> 405 with an Allow header.
        $allowed = $this->allowedMethodsFor($path);

        if ($allowed !== []) {
            throw new MethodNotAllowedException($allowed);
        }

        throw new NotFoundException('The requested endpoint does not exist.');
    }

    /**
     * Collect the HTTP methods registered for a given path.
     *
     * @return array<int, string>
     */
    private function allowedMethodsFor(string $path): array
    {
        $methods = [];

        foreach ($this->routes as $route) {
            if ($route->matchesPath($path)) {
                $methods[] = $route->method();
            }
        }

        return array_values(array_unique($methods));
    }

    /**
     * Run a matched route through its middleware pipeline to a response.
     */
    private function runRoute(Route $route, Request $request): Response
    {
        $destination = fn(Request $req): Response => $this->callHandler($route, $req);

        return (new Pipeline($this->container))
            ->through($route->getMiddleware())
            ->run($request, $destination);
    }

    /**
     * @return array<int, Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * Resolve and invoke a route handler, always returning a Response.
     */
    private function callHandler(Route $route, Request $request): Response
    {
        $handler = $route->handler();

        if (is_array($handler)) {
            [$target, $method] = $handler;
            // Target may be a class name (resolved from the container) or an
            // already-instantiated controller object.
            $controller = is_string($target) ? $this->container->get($target) : $target;
            $result     = $controller->{$method}($request);
        } else {
            $result = $handler($request);
        }

        if (!$result instanceof Response) {
            throw new \RuntimeException(sprintf(
                'Handler for "%s %s" must return a Response instance.',
                $route->method(),
                $route->path()
            ));
        }

        return $result;
    }
}
