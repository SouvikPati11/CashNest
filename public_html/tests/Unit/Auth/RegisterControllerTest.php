<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Contracts\RegistrationServiceInterface;
use App\Controllers\Auth\RegisterController;
use App\Exceptions\ValidationException;
use App\Models\User;
use Core\Http\Request;
use PHPUnit\Framework\TestCase;

final class RegisterControllerTest extends TestCase
{
    private function controller(): RegisterController
    {
        $service = new class implements RegistrationServiceInterface {
            public function register(array $data): User
            {
                return User::fromRow([
                    'id'            => 1,
                    'uuid'          => 'u-abc',
                    'name'          => $data['name'] ?? null,
                    'email'         => $data['email'] ?? null,
                    'referral_code' => 'ABC12345',
                    'status'        => 'active',
                ]);
            }
        };

        return new RegisterController($service);
    }

    private function request(array $body): Request
    {
        return new Request('POST', '/v1/auth/email/register', [], $body);
    }

    public function testSuccessfulRegistrationReturns201Envelope(): void
    {
        $response = $this->controller()->register($this->request([
            'name'     => 'Asha',
            'email'    => 'asha@example.com',
            'password' => 'passw0rd',
        ]));

        self::assertSame(201, $response->status());

        $payload = json_decode($response->body(), true);
        self::assertSame('success', $payload['status']);
        self::assertTrue($payload['data']['verification_required']);
        self::assertSame('asha@example.com', $payload['data']['user']['email']);
        self::assertTrue($payload['data']['user']['is_new']);
    }

    public function testInvalidInputThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        // Missing name, invalid email, weak password.
        $this->controller()->register($this->request([
            'email'    => 'not-an-email',
            'password' => 'short',
        ]));
    }

    public function testPasswordWithoutDigitIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->controller()->register($this->request([
            'name'     => 'Asha',
            'email'    => 'asha@example.com',
            'password' => 'onlyletters',
        ]));
    }
}
