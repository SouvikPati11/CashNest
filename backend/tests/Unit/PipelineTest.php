<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Container;
use Core\Contracts\MiddlewareInterface;
use Core\Http\Pipeline;
use Core\Http\Request;
use Core\Http\Response;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
    public function testRunsDestinationWhenNoMiddleware(): void
    {
        $pipeline = new Pipeline(new Container());

        $response = $pipeline->through([])->run(
            new Request('GET', '/'),
            static fn(Request $r): Response => Response::json(['ok' => true])
        );

        self::assertSame(200, $response->status());
    }

    public function testMiddlewareWrapsResponseInOrder(): void
    {
        $container = new Container();

        $first = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return $next($request)->withHeader('X-Order', 'first');
            }
        };

        $second = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                // Runs closer to the destination; "first" overwrites afterwards.
                return $next($request)->withHeader('X-Order', 'second');
            }
        };

        $pipeline = new Pipeline($container);

        $response = $pipeline->through([$first, $second])->run(
            new Request('GET', '/'),
            static fn(Request $r): Response => Response::json([])
        );

        self::assertSame('first', $response->headers()['X-Order']);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $blocker = new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next): Response
            {
                return Response::json(['blocked' => true], 403);
            }
        };

        $pipeline = new Pipeline(new Container());

        $response = $pipeline->through([$blocker])->run(
            new Request('GET', '/'),
            static fn(Request $r): Response => Response::json(['reached' => true])
        );

        self::assertSame(403, $response->status());
        self::assertStringContainsString('blocked', $response->body());
    }
}
