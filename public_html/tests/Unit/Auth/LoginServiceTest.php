<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Models\AuthProvider;
use App\Services\AuthenticationService;
use App\Services\LoginService;
use App\Services\UserService;
use Core\Config;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAuthenticationRepository;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\NullLogger;

final class LoginServiceTest extends TestCase
{
    private InMemoryUserRepository $users;

    private InMemoryAuthenticationRepository $providers;

    private UserService $userService;

    private AuthenticationService $authService;

    private LoginService $service;

    protected function setUp(): void
    {
        $this->users       = new InMemoryUserRepository();
        $this->providers   = new InMemoryAuthenticationRepository();
        $this->userService = new UserService($this->users, new NullLogger());
        $this->authService = new AuthenticationService($this->providers, new NullLogger());
        // Enable the email-verification gate so the unverified-login test holds.
        $config            = new Config(['auth' => ['require_email_verification' => true]]);
        $this->service     = new LoginService($this->authService, $this->userService, $config, new NullLogger());
    }

    /**
     * Seed a user + email identity; returns the user id.
     */
    private function seedUser(string $email, string $password, bool $verified = true, string $status = 'active'): int
    {
        $user = $this->userService->createUser(['email' => $email, 'status' => $status]);
        $id   = (int) $user->id();

        if ($verified) {
            $this->users->update($id, ['email_verified_at' => '2026-01-01 00:00:00']);
        }

        $this->authService->createProvider([
            'user_id'       => $id,
            'provider'      => AuthProvider::PROVIDER_EMAIL,
            'email'         => strtolower($email),
            'password_hash' => $this->authService->hashPassword($password),
            'is_primary'    => true,
        ]);

        return $id;
    }

    public function testSuccessfulLoginReturnsUserAndTouchesLastLogin(): void
    {
        $id   = $this->seedUser('user@example.com', 'S3cret!pass');
        $user = $this->service->login('USER@example.com', 'S3cret!pass');

        self::assertSame($id, $user->id());
        self::assertNotNull($this->users->rows[$id]['last_login_at'] ?? null);
    }

    public function testWrongPasswordIsRejected(): void
    {
        $this->seedUser('user@example.com', 'correct-pass');

        try {
            $this->service->login('user@example.com', 'wrong-pass');
            self::fail('Expected INVALID_CREDENTIALS.');
        } catch (UnauthorizedException $e) {
            self::assertSame('INVALID_CREDENTIALS', $e->getErrorCode());
        }
    }

    public function testUnknownEmailIsRejectedUniformly(): void
    {
        try {
            $this->service->login('ghost@example.com', 'whatever');
            self::fail('Expected INVALID_CREDENTIALS.');
        } catch (UnauthorizedException $e) {
            self::assertSame('INVALID_CREDENTIALS', $e->getErrorCode());
        }
    }

    public function testUnverifiedEmailIsForbidden(): void
    {
        $this->seedUser('unverified@example.com', 'S3cret!pass', verified: false);

        try {
            $this->service->login('unverified@example.com', 'S3cret!pass');
            self::fail('Expected EMAIL_NOT_VERIFIED.');
        } catch (ForbiddenException $e) {
            self::assertSame('EMAIL_NOT_VERIFIED', $e->getErrorCode());
        }
    }

    public function testBlockedAccountIsForbidden(): void
    {
        $this->seedUser('banned@example.com', 'S3cret!pass', verified: true, status: 'banned');

        try {
            $this->service->login('banned@example.com', 'S3cret!pass');
            self::fail('Expected ACCOUNT_SUSPENDED.');
        } catch (ForbiddenException $e) {
            self::assertSame('ACCOUNT_SUSPENDED', $e->getErrorCode());
        }
    }
}
