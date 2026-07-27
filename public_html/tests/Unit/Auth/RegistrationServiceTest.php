<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Contracts\EmailVerificationServiceInterface;
use App\Exceptions\HttpException;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Services\AuthenticationService;
use App\Services\RegistrationService;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryAuthenticationRepository;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\NullLogger;

final class RegistrationServiceTest extends TestCase
{
    private InMemoryUserRepository $users;

    private InMemoryAuthenticationRepository $providers;

    private RegistrationService $service;

    /** @var EmailVerificationServiceInterface&object{sent:int} */
    private object $verification;

    protected function setUp(): void
    {
        $this->users     = new InMemoryUserRepository();
        $this->providers = new InMemoryAuthenticationRepository();

        $userService = new UserService($this->users, new NullLogger());
        $authService = new AuthenticationService($this->providers, new NullLogger());

        $this->verification = new class implements EmailVerificationServiceInterface {
            public int $sent = 0;

            public function sendVerification(User $user): void
            {
                $this->sent++;
            }

            public function verifyToken(string $token): ?int
            {
                return null;
            }

            public function verifyOtp(string $email, string $otp): ?int
            {
                return null;
            }

            public function markVerified(int $userId): void
            {
            }
        };

        $this->service = new RegistrationService(
            new FakeTransactionRunner(),
            $userService,
            $authService,
            $this->users,
            $this->verification,
            new NullLogger()
        );
    }

    public function testRegisterCreatesUserAndEmailIdentity(): void
    {
        $user = $this->service->register([
            'name'     => 'Asha',
            'email'    => 'ASHA@Example.com',
            'password' => 'passw0rd',
        ]);

        self::assertInstanceOf(User::class, $user);
        self::assertSame('asha@example.com', $user->email());
        self::assertCount(1, $this->users->rows);
        self::assertCount(1, $this->providers->rows);

        $provider = $this->providers->rows[1];
        self::assertSame('email', $provider['provider']);
        self::assertSame('asha@example.com', $provider['email']);
        self::assertNotEmpty($provider['password_hash']);
        self::assertNotSame('passw0rd', $provider['password_hash']);
        self::assertSame(1, $this->verification->sent);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $this->service->register(['name' => 'A', 'email' => 'dup@example.com', 'password' => 'passw0rd']);

        try {
            $this->service->register(['name' => 'B', 'email' => 'DUP@example.com', 'password' => 'passw0rd']);
            self::fail('Expected EMAIL_EXISTS.');
        } catch (HttpException $e) {
            self::assertSame(409, $e->getStatusCode());
            self::assertSame('EMAIL_EXISTS', $e->getErrorCode());
        }
    }

    public function testRegisterRejectsInvalidReferralCode(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->register([
            'name'          => 'A',
            'email'         => 'a@example.com',
            'password'      => 'passw0rd',
            'referral_code' => 'NOPE123',
        ]);
    }

    public function testRegisterLinksValidReferralCode(): void
    {
        $referrer = $this->service->register([
            'name'     => 'Referrer',
            'email'    => 'ref@example.com',
            'password' => 'passw0rd',
        ]);

        $code = (string) $referrer->get('referral_code');

        $referee = $this->service->register([
            'name'          => 'Referee',
            'email'         => 'new@example.com',
            'password'      => 'passw0rd',
            'referral_code' => $code,
        ]);

        self::assertSame($referrer->id(), $referee->get('referred_by'));
    }
}
