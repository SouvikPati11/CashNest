<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Contracts\GoogleTokenVerifierInterface;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;
use App\Models\AuthProvider;
use App\Services\AuthenticationService;
use App\Services\AuthTokenService;
use App\Services\GoogleLoginService;
use App\Services\UserService;
use Core\Config;
use Core\Security\JwtService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryAuthenticationRepository;
use Tests\Support\InMemorySessionRepository;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\NullLogger;

final class GoogleLoginServiceTest extends TestCase
{
    private InMemoryUserRepository $userRepo;

    private InMemoryAuthenticationRepository $providers;

    private UserService $userService;

    private AuthenticationService $authService;

    /** @var GoogleTokenVerifierInterface&object{claims: array<string, mixed>} */
    private object $verifier;

    private GoogleLoginService $service;

    protected function setUp(): void
    {
        $this->userRepo    = new InMemoryUserRepository();
        $this->providers   = new InMemoryAuthenticationRepository();
        $this->userService = new UserService($this->userRepo, new NullLogger());
        $this->authService = new AuthenticationService($this->providers, new NullLogger());

        $tokenService = new AuthTokenService(
            new JwtService('g-test-secret-key-0123456789abcdef', 'HS256', 'cashnest', 'cashnest-app'),
            new InMemorySessionRepository(),
            $this->userService,
            new Config(['jwt' => ['access_ttl' => 1800, 'refresh_ttl' => 2592000]]),
            new NullLogger()
        );

        $this->verifier = new class implements GoogleTokenVerifierInterface {
            /** @var array<string, mixed> */
            public array $claims = [];

            public function verify(string $idToken): array
            {
                /** @var array{sub: string, email: ?string, name: ?string, picture: ?string, email_verified: bool} */
                return $this->claims;
            }
        };

        $this->service = new GoogleLoginService(
            $this->verifier,
            $this->userService,
            $this->authService,
            $tokenService,
            new FakeTransactionRunner(),
            $this->userRepo,
            new NullLogger()
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function claims(array $overrides = []): array
    {
        return array_merge([
            'sub'            => 'g-sub-1',
            'email'          => 'new@example.com',
            'name'           => 'New User',
            'picture'        => 'https://pic',
            'email_verified' => true,
        ], $overrides);
    }

    public function testNewUserIsCreatedVerifiedAndTokensIssued(): void
    {
        $this->verifier->claims = $this->claims();

        $result = $this->service->login('id-token', null, '1.2.3.4', 'agent');

        self::assertTrue($result['is_new']);
        self::assertSame('new@example.com', $result['user']->email());
        self::assertNotEmpty($result['tokens']['access_token']);
        self::assertNotEmpty($result['tokens']['refresh_token']);

        // Google identity persisted.
        self::assertCount(1, $this->providers->rows);
        self::assertSame('google', $this->providers->rows[1]['provider']);
        self::assertSame('g-sub-1', $this->providers->rows[1]['provider_uid']);

        // Google-verified => email marked verified.
        $id = (int) $result['user']->id();
        self::assertNotNull($this->userRepo->rows[$id]['email_verified_at'] ?? null);
    }

    public function testExistingGoogleIdentityLogsIn(): void
    {
        $user = $this->userService->createUser(['email' => 'existing@example.com']);
        $this->authService->createProvider([
            'user_id'      => $user->id(),
            'provider'     => AuthProvider::PROVIDER_GOOGLE,
            'provider_uid' => 'g-sub-2',
        ]);

        $this->verifier->claims = $this->claims(['sub' => 'g-sub-2', 'email' => 'existing@example.com']);

        $result = $this->service->login('id-token', null, null, null);

        self::assertFalse($result['is_new']);
        self::assertSame($user->id(), $result['user']->id());
        self::assertCount(1, $this->providers->rows); // no new identity created
    }

    public function testLinksGoogleToExistingEmailAccount(): void
    {
        $user = $this->userService->createUser(['email' => 'link@example.com']);

        $this->verifier->claims = $this->claims(['sub' => 'g-sub-3', 'email' => 'link@example.com']);

        $result = $this->service->login('id-token', null, null, null);

        self::assertFalse($result['is_new']);
        self::assertSame($user->id(), $result['user']->id());
        self::assertCount(1, $this->providers->rows);
        self::assertSame('g-sub-3', $this->providers->rows[1]['provider_uid']);
    }

    public function testInvalidReferralCodeIsRejected(): void
    {
        $this->verifier->claims = $this->claims(['sub' => 'g-sub-4', 'email' => 'ref@example.com']);

        $this->expectException(ValidationException::class);
        $this->service->login('id-token', 'BADCODE1', null, null);
    }

    public function testBlockedAccountIsForbidden(): void
    {
        $user = $this->userService->createUser(['email' => 'banned@example.com', 'status' => 'banned']);
        $this->authService->createProvider([
            'user_id'      => $user->id(),
            'provider'     => AuthProvider::PROVIDER_GOOGLE,
            'provider_uid' => 'g-sub-5',
        ]);

        $this->verifier->claims = $this->claims(['sub' => 'g-sub-5', 'email' => 'banned@example.com']);

        $this->expectException(ForbiddenException::class);
        $this->service->login('id-token', null, null, null);
    }
}
