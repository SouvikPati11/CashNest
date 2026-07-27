<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Contracts\LoginServiceInterface;
use App\Controllers\Auth\LoginController;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\User;
use Core\Http\Request;
use PHPUnit\Framework\TestCase;

final class LoginControllerTest extends TestCase
{
    private function controller(bool $succeeds = true): LoginController
    {
        $service = new class ($succeeds) implements LoginServiceInterface {
            public function __construct(private bool $succeeds)
            {
            }

            public function login(string $email, string $password): User
            {
                if (!$this->succeeds) {
                    throw new UnauthorizedException('Invalid email or password.', 'INVALID_CREDENTIALS');
                }

                return User::fromRow([
                    'id'            => 1,
                    'uuid'          => 'u-1',
                    'email'         => $email,
                    'referral_code' => 'CODE1234',
                    'status'        => 'active',
                ]);
            }
        };

        return new LoginController($service);
    }

    private function request(array $body): Request
    {
        return new Request('POST', '/v1/auth/email/login', [], $body);
    }

    public function testSuccessfulLoginReturns200WithNullTokens(): void
    {
        $response = $this->controller()->login($this->request([
            'email'    => 'user@example.com',
            'password' => 'S3cret!pass',
        ]));

        self::assertSame(200, $response->status());

        $payload = json_decode($response->body(), true);
        self::assertSame('success', $payload['status']);
        self::assertNull($payload['data']['tokens']);
        self::assertSame('user@example.com', $payload['data']['user']['email']);
    }

    public function testInvalidCredentialsPropagate(): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->controller(succeeds: false)->login($this->request([
            'email'    => 'user@example.com',
            'password' => 'wrong',
        ]));
    }

    public function testMissingPasswordFailsValidation(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller()->login($this->request(['email' => 'user@example.com']));
    }
}
