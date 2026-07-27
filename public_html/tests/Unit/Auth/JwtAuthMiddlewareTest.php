<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Middleware\JwtAuthMiddleware;
use App\Models\User;
use App\Services\UserService;
use Core\Exceptions\TokenException;
use Core\Http\Request;
use Core\Http\Response;
use Core\Security\JwtService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\NullLogger;

final class JwtAuthMiddlewareTest extends TestCase
{
    private JwtService $jwt;

    private InMemoryUserRepository $userRepo;

    private UserService $userService;

    private JwtAuthMiddleware $middleware;

    protected function setUp(): void
    {
        $this->jwt         = new JwtService('mw-test-secret-key-0123456789abcdef', 'HS256', 'cashnest', 'cashnest-app');
        $this->userRepo    = new InMemoryUserRepository();
        $this->userService = new UserService($this->userRepo, new NullLogger());
        $this->middleware  = new JwtAuthMiddleware($this->jwt, $this->userService);
    }

    private function seedUser(string $status = 'active'): User
    {
        return $this->userService->createUser(['email' => 'mw@example.com', 'status' => $status]);
    }

    private function requestWithToken(?string $token): Request
    {
        $headers = $token !== null ? ['authorization' => 'Bearer ' . $token] : [];

        return new Request('GET', '/protected', [], [], $headers);
    }

    public function testValidTokenPassesAndAttachesUser(): void
    {
        $user  = $this->seedUser();
        $token = $this->jwt->issue((string) $user->uuid(), ['scope' => 'user'], 60);

        $captured = null;
        $response = $this->middleware->handle(
            $this->requestWithToken($token),
            function (Request $req) use (&$captured): Response {
                $captured = $req;
                return Response::json(['ok' => true]);
            }
        );

        self::assertSame(200, $response->status());
        self::assertNotNull($captured);
        self::assertInstanceOf(User::class, $captured->attribute('user'));
        self::assertSame($user->id(), $captured->attribute('user_id'));
        self::assertSame($user->uuid(), $captured->attribute('user_uuid'));
    }

    public function testMissingTokenIsRejected(): void
    {
        try {
            $this->middleware->handle($this->requestWithToken(null), fn(Request $r): Response => Response::json([]));
            self::fail('Expected AUTH_REQUIRED.');
        } catch (UnauthorizedException $e) {
            self::assertSame('AUTH_REQUIRED', $e->getErrorCode());
        }
    }

    public function testInvalidTokenThrowsTokenException(): void
    {
        $this->expectException(TokenException::class);

        $this->middleware->handle(
            $this->requestWithToken('not-a-real-jwt'),
            fn(Request $r): Response => Response::json([])
        );
    }

    public function testExpiredTokenThrowsExpired(): void
    {
        $user  = $this->seedUser();
        $token = $this->jwt->issue((string) $user->uuid(), [], -10); // already expired

        try {
            $this->middleware->handle($this->requestWithToken($token), fn(Request $r): Response => Response::json([]));
            self::fail('Expected expired TokenException.');
        } catch (TokenException $e) {
            self::assertSame(TokenException::REASON_EXPIRED, $e->reason());
        }
    }

    public function testBlockedUserIsForbidden(): void
    {
        $user  = $this->seedUser('banned');
        $token = $this->jwt->issue((string) $user->uuid(), [], 60);

        try {
            $this->middleware->handle($this->requestWithToken($token), fn(Request $r): Response => Response::json([]));
            self::fail('Expected ACCOUNT_SUSPENDED.');
        } catch (ForbiddenException $e) {
            self::assertSame('ACCOUNT_SUSPENDED', $e->getErrorCode());
        }
    }
}
