<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Contracts\AuthTokenServiceInterface;
use App\Contracts\GoogleLoginServiceInterface;
use App\Controllers\Auth\GoogleLoginController;
use App\Controllers\Auth\LogoutController;
use App\Controllers\Auth\RefreshTokenController;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Models\User;
use Core\Http\Request;
use PHPUnit\Framework\TestCase;

final class TokenControllersTest extends TestCase
{
    /**
     * @return array{access_token: string, token_type: string, expires_in: int, refresh_token: string}
     */
    private function tokenPair(): array
    {
        return [
            'access_token'  => 'access.jwt',
            'token_type'    => 'Bearer',
            'expires_in'    => 1800,
            'refresh_token' => 'refresh-abc',
        ];
    }

    private function googleService(): GoogleLoginServiceInterface
    {
        $pair = $this->tokenPair();

        return new class ($pair) implements GoogleLoginServiceInterface {
            /** @param array{access_token: string, token_type: string, expires_in: int, refresh_token: string} $pair */
            public function __construct(private array $pair)
            {
            }

            public function login(string $idToken, ?string $referralCode, ?string $ip, ?string $userAgent): array
            {
                $user = User::fromRow([
                    'id'            => 1,
                    'uuid'          => 'u-1',
                    'email'         => 'g@example.com',
                    'referral_code' => 'GCODE123',
                    'status'        => 'active',
                ]);

                return ['user' => $user, 'tokens' => $this->pair, 'is_new' => true];
            }
        };
    }

    public function testGoogleLoginReturns200WithTokens(): void
    {
        $controller = new GoogleLoginController($this->googleService());

        $response = $controller->login(
            new Request('POST', '/v1/auth/google', [], ['id_token' => 'valid-google-token'])
        );

        self::assertSame(200, $response->status());
        $payload = json_decode($response->body(), true);
        self::assertSame('g@example.com', $payload['data']['user']['email']);
        self::assertTrue($payload['data']['user']['is_new']);
        self::assertSame('Bearer', $payload['data']['tokens']['token_type']);
    }

    public function testGoogleLoginRequiresIdToken(): void
    {
        $this->expectException(ValidationException::class);

        (new GoogleLoginController($this->googleService()))
            ->login(new Request('POST', '/v1/auth/google', [], []));
    }

    public function testRefreshReturnsNewTokens(): void
    {
        $pair    = $this->tokenPair();
        $service = new class ($pair) implements AuthTokenServiceInterface {
            /** @param array{access_token: string, token_type: string, expires_in: int, refresh_token: string} $pair */
            public function __construct(private array $pair)
            {
            }

            public function issueTokens(User $user, ?string $ip, ?string $userAgent): array
            {
                return $this->pair;
            }

            public function refresh(string $refreshToken): array
            {
                return $this->pair;
            }

            public function logout(string $refreshToken): void
            {
            }

            public function logoutAll(int $userId): void
            {
            }
        };

        $response = (new RefreshTokenController($service))
            ->refresh(new Request('POST', '/v1/auth/refresh', [], ['refresh_token' => 'refresh-abc']));

        self::assertSame(200, $response->status());
        $payload = json_decode($response->body(), true);
        self::assertSame('refresh-abc', $payload['data']['tokens']['refresh_token']);
    }

    public function testRefreshRequiresToken(): void
    {
        $service = $this->nullTokenService();

        $this->expectException(ValidationException::class);
        (new RefreshTokenController($service))->refresh(new Request('POST', '/v1/auth/refresh', [], []));
    }

    public function testLogoutWithRefreshTokenRevokesThatSession(): void
    {
        $service = $this->recordingTokenService();

        $response = (new LogoutController($service))
            ->logout(new Request('POST', '/v1/auth/logout', [], ['refresh_token' => 'refresh-abc']));

        self::assertSame(200, $response->status());
        self::assertSame('refresh-abc', $service->loggedOut);
        self::assertSame(0, $service->loggedOutAll);
    }

    public function testLogoutWithoutRefreshTokenRevokesAllForUser(): void
    {
        $service = $this->recordingTokenService();

        $request = new Request('POST', '/v1/auth/logout', [], []);
        $request->setAttribute('user_id', 42); // set by JWT middleware

        (new LogoutController($service))->logout($request);

        self::assertSame(42, $service->loggedOutAll);
        self::assertNull($service->loggedOut);
    }

    private function nullTokenService(): AuthTokenServiceInterface
    {
        return new class implements AuthTokenServiceInterface {
            public function issueTokens(User $user, ?string $ip, ?string $userAgent): array
            {
                return ['access_token' => '', 'token_type' => 'Bearer', 'expires_in' => 0, 'refresh_token' => ''];
            }

            public function refresh(string $refreshToken): array
            {
                throw new UnauthorizedException('nope', 'INVALID_REFRESH_TOKEN');
            }

            public function logout(string $refreshToken): void
            {
            }

            public function logoutAll(int $userId): void
            {
            }
        };
    }

    /**
     * @return AuthTokenServiceInterface&object{loggedOut: ?string, loggedOutAll: int}
     */
    private function recordingTokenService(): object
    {
        return new class implements AuthTokenServiceInterface {
            public ?string $loggedOut = null;

            public int $loggedOutAll = 0;

            public function issueTokens(User $user, ?string $ip, ?string $userAgent): array
            {
                return ['access_token' => '', 'token_type' => 'Bearer', 'expires_in' => 0, 'refresh_token' => ''];
            }

            public function refresh(string $refreshToken): array
            {
                return ['access_token' => '', 'token_type' => 'Bearer', 'expires_in' => 0, 'refresh_token' => ''];
            }

            public function logout(string $refreshToken): void
            {
                $this->loggedOut = $refreshToken;
            }

            public function logoutAll(int $userId): void
            {
                $this->loggedOutAll = $userId;
            }
        };
    }
}
