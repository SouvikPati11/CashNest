<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\NotFoundException;
use Core\Container;
use Core\Http\Request;
use Core\Http\Response;
use Core\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private function makeRequest(string $method, string $path): Request
    {
        return new Request($method, $path);
    }

    public function testMatchesSimpleRoute(): void
    {
        $router = new Router(new Container());
        $router->get('/v1/health', static fn(Request $r): Response => Response::json(['ok' => true]));

        $response = $router->dispatch($this->makeRequest('GET', '/v1/health'));

        self::assertSame(200, $response->status());
    }

    public function testCapturesRouteParameters(): void
    {
        $router = new Router(new Container());
        $router->get('/v1/items/{id}', static function (Request $r): Response {
            return Response::json(['id' => $r->routeParam('id')]);
        });

        $response = $router->dispatch($this->makeRequest('GET', '/v1/items/abc123'));

        self::assertStringContainsString('abc123', $response->body());
    }

    public function testGroupPrefixApplies(): void
    {
        $router = new Router(new Container());
        $router->group(['prefix' => 'v1'], static function (Router $router): void {
            $router->get('/ping', static fn(Request $r): Response => Response::json(['pong' => true]));
        });

        $response = $router->dispatch($this->makeRequest('GET', '/v1/ping'));

        self::assertSame(200, $response->status());
    }

    public function testUnmatchedRouteThrowsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $router = new Router(new Container());
        $router->dispatch($this->makeRequest('GET', '/nope'));
    }

    public function testMethodMismatchDoesNotMatch(): void
    {
        $this->expectException(NotFoundException::class);

        $router = new Router(new Container());
        $router->get('/only-get', static fn(Request $r): Response => Response::json([]));
        $router->dispatch($this->makeRequest('POST', '/only-get'));
    }
}
