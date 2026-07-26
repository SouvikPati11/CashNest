<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Contracts\EmailVerificationServiceInterface;
use App\Controllers\Auth\EmailVerificationController;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Services\UserService;
use Core\Http\Request;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\NullLogger;

final class EmailVerificationControllerTest extends TestCase
{
    private InMemoryUserRepository $users;

    private UserService $userService;

    protected function setUp(): void
    {
        $this->users       = new InMemoryUserRepository();
        $this->userService = new UserService($this->users, new NullLogger());
    }

    /**
     * @param int|null $resolvesTo user id the fake verifier returns, or null to reject.
     */
    private function controller(?int $resolvesTo): EmailVerificationController
    {
        $verification = new class ($resolvesTo) implements EmailVerificationServiceInterface {
            public int $markedUserId = 0;

            public function __construct(private ?int $resolvesTo)
            {
            }

            public function sendVerification(User $user): void
            {
            }

            public function verifyToken(string $token): ?int
            {
                return $this->resolvesTo;
            }

            public function verifyOtp(string $email, string $otp): ?int
            {
                return $this->resolvesTo;
            }

            public function markVerified(int $userId): void
            {
                $this->markedUserId = $userId;
            }
        };

        return new EmailVerificationController($verification, $this->userService);
    }

    private function request(array $body): Request
    {
        return new Request('POST', '/v1/auth/email/verify', [], $body);
    }

    public function testSuccessfulTokenVerificationReturns200(): void
    {
        $user = $this->userService->createUser(['email' => 'v@example.com']);
        $id   = (int) $user->id();

        $response = $this->controller($id)->verify($this->request([
            'email' => 'v@example.com',
            'token' => 'valid-token',
        ]));

        self::assertSame(200, $response->status());

        $payload = json_decode($response->body(), true);
        self::assertTrue($payload['data']['verified']);
        self::assertNull($payload['data']['tokens']);
        self::assertSame('v@example.com', $payload['data']['user']['email']);
    }

    public function testMissingTokenAndOtpFailsValidation(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller(1)->verify($this->request(['email' => 'v@example.com']));
    }

    public function testInvalidTokenIsUnauthorized(): void
    {
        try {
            $this->controller(null)->verify($this->request([
                'email' => 'v@example.com',
                'token' => 'bad',
            ]));
            self::fail('Expected INVALID_TOKEN.');
        } catch (UnauthorizedException $e) {
            self::assertSame('INVALID_TOKEN', $e->getErrorCode());
        }
    }
}
