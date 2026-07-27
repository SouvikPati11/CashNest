<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Helpers\Security;
use App\Models\User;
use App\Services\AuthTokenService;
use App\Services\UserService;
use Core\Config;
use Core\Security\JwtService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemorySessionRepository;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\NullLogger;

final class AuthTokenServiceTest extends TestCase
{
    private InMemoryUserRepository $userRepo;

    private InMemorySessionRepository $sessions;

    private UserService $userService;

    private AuthTokenService $service;

    protected function setUp(): void
    {
        $this->userRepo    = new InMemoryUserRepository();
        $this->sessions    = new InMemorySessionRepository();
        $this->userService = new UserService($this->userRepo, new NullLogger());

        $jwt    = new JwtService('unit-test-secret-key-0123456789abcdef', 'HS256', 'cashnest', 'cashnest-app');
        $config = new Config(['jwt' => ['access_ttl' => 1800, 'refresh_ttl' => 2592000]]);

        $this->service = new AuthTokenService($jwt, $this->sessions, $this->userService, $config, new NullLogger());
    }

    private function makeUser(string $status = 'active'): User
    {
        return $this->userService->createUser(['email' => 'u@example.com', 'status' => $status]);
    }

    public function testIssueTokensReturnsPairAndCreatesSession(): void
    {
        $tokens = $this->service->issueTokens($this->makeUser(), '1.2.3.4', 'agent');

        self::assertSame('Bearer', $tokens['token_type']);
        self::assertSame(1800, $tokens['expires_in']);
        self::assertNotEmpty($tokens['access_token']);
        self::assertNotEmpty($tokens['refresh_token']);
        self::assertCount(1, $this->sessions->rows);
        // Only the hash is stored, never the raw token.
        self::assertSame(Security::sha256($tokens['refresh_token']), $this->sessions->rows[1]['refresh_token_hash']);
    }

    public function testRefreshRotatesTokens(): void
    {
        $first = $this->service->issueTokens($this->makeUser(), null, null);

        $second = $this->service->refresh($first['refresh_token']);

        self::assertNotSame($first['refresh_token'], $second['refresh_token']);
        self::assertNotNull($this->sessions->rows[1]['revoked_at']); // old session revoked
        self::assertCount(2, $this->sessions->rows);                 // new session created
    }

    public function testUnknownRefreshTokenIsRejected(): void
    {
        try {
            $this->service->refresh('does-not-exist');
            self::fail('Expected INVALID_REFRESH_TOKEN.');
        } catch (UnauthorizedException $e) {
            self::assertSame('INVALID_REFRESH_TOKEN', $e->getErrorCode());
        }
    }

    public function testReuseOfRevokedTokenRevokesWholeChain(): void
    {
        $first = $this->service->issueTokens($this->makeUser(), null, null);
        $this->service->refresh($first['refresh_token']); // rotates; session 1 revoked, session 2 active

        try {
            $this->service->refresh($first['refresh_token']); // reuse of revoked token
            self::fail('Expected INVALID_REFRESH_TOKEN.');
        } catch (UnauthorizedException $e) {
            self::assertSame('INVALID_REFRESH_TOKEN', $e->getErrorCode());
        }

        // The rotated (session 2) token must also be revoked now.
        self::assertNotNull($this->sessions->rows[2]['revoked_at']);
    }

    public function testExpiredRefreshTokenIsRejected(): void
    {
        $user = $this->makeUser();
        $this->sessions->create([
            'user_id'            => $user->id(),
            'refresh_token_hash' => Security::sha256('raw-expired'),
            'expires_at'         => gmdate('Y-m-d H:i:s', time() - 60),
            'revoked_at'         => null,
        ]);

        $this->expectException(UnauthorizedException::class);
        $this->service->refresh('raw-expired');
    }

    public function testBlockedUserCannotRefresh(): void
    {
        $user  = $this->makeUser('banned');
        $first = $this->service->issueTokens($user, null, null);

        try {
            $this->service->refresh($first['refresh_token']);
            self::fail('Expected ACCOUNT_SUSPENDED.');
        } catch (ForbiddenException $e) {
            self::assertSame('ACCOUNT_SUSPENDED', $e->getErrorCode());
        }
    }

    public function testLogoutRevokesSession(): void
    {
        $tokens = $this->service->issueTokens($this->makeUser(), null, null);

        $this->service->logout($tokens['refresh_token']);

        self::assertNotNull($this->sessions->rows[1]['revoked_at']);
        // Idempotent: logging out again does not error.
        $this->service->logout($tokens['refresh_token']);
        self::assertTrue(true);
    }

    public function testLogoutAllRevokesEverySession(): void
    {
        $user = $this->makeUser();
        $this->service->issueTokens($user, null, null);
        $this->service->issueTokens($user, null, null);

        $this->service->logoutAll((int) $user->id());

        foreach ($this->sessions->rows as $row) {
            self::assertNotNull($row['revoked_at']);
        }
    }
}
