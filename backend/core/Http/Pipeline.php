<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Contracts\ContainerInterface;
use Core\Contracts\MiddlewareInterface;

/**
 * Middleware pipeline.
 *
 * Composes a list of middleware around a final destination handler using the
 * classic onion pattern. Middleware are resolved lazily from the container so
 * they can declare their own dependencies.
 */
final class Pipeline
{
    /** @var array<int, class-string<MiddlewareInterface>|MiddlewareInterface> */
    private array $middleware = [];

    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * Set the middleware stack (outermost first).
     *
     * @param array<int, class-string<MiddlewareInterface>|MiddlewareInterface> $middleware
     */
    public function through(array $middleware): self
    {
        $this->middleware = $middleware;

        return $this;
    }

    /**
     * Run the pipeline, ending at the given destination handler.
     *
     * @param callable(Request): Response $destination
     */
    public function run(Request $request, callable $destination): Response
    {
        $chain = array_reduce(
            array_reverse($this->middleware),
            function (callable $next, string|MiddlewareInterface $middleware): callable {
                return function (Request $request) use ($middleware, $next): Response {
                    $instance = $middleware instanceof MiddlewareInterface
                        ? $middleware
                        : $this->container->get($middleware);

                    if (!$instance instanceof MiddlewareInterface) {
                        throw new \RuntimeException(sprintf(
                            'Middleware "%s" must implement MiddlewareInterface.',
                            is_object($instance) ? $instance::class : (string) $instance
                        ));
                    }

                    return $instance->handle($request, $next);
                };
            },
            $destination
        );

        return $chain($request);
    }
}
