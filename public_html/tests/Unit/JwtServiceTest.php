<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Exceptions\TokenException;
use Core\Security\JwtService;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    private function service(): JwtService
    {
        return new JwtService('a-very-long-test-secret-key-0123456789', 'HS256', 'cashnest', 'cashnest-app');
    }

    public function testIssueAndVerifyRoundTrip(): void
    {
        $service = $this->service();
        $token   = $service->issue('user-uuid-123', ['scope' => 'user'], 60);

        $claims = $service->verify($token);

        self::assertSame('user-uuid-123', $claims['sub']);
        self::assertSame('user', $claims['scope']);
        self::assertSame('cashnest', $claims['iss']);
    }

    public function testExpiredTokenThrowsExpired(): void
    {
        $service = $this->service();
        $token   = $service->issue('user-1', [], -10); // already expired

        try {
            $service->verify($token);
            self::fail('Expected TokenException.');
        } catch (TokenException $e) {
            self::assertSame(TokenException::REASON_EXPIRED, $e->reason());
        }
    }

    public function testTamperedTokenThrowsInvalid(): void
    {
        $service = $this->service();
        $token   = $service->issue('user-1', [], 60) . 'tamper';

        $this->expectException(TokenException::class);
        $service->verify($token);
    }

    public function testWrongAudienceRejected(): void
    {
        $issuer = new JwtService('secret-secret-secret-secret-secret', 'HS256', 'cashnest', 'other-aud');
        $token  = $issuer->issue('user-1', [], 60);

        $verifier = $this->service(); // expects cashnest-app

        $this->expectException(TokenException::class);
        $verifier->verify($token);
    }
}
