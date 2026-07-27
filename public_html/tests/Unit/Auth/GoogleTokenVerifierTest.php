<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Exceptions\UnauthorizedException;
use App\Services\GoogleTokenVerifier;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeHttpFetcher;
use Tests\Support\NullLogger;

final class GoogleTokenVerifierTest extends TestCase
{
    private const CLIENT_ID = 'client-123.apps.googleusercontent.com';

    private function verifier(FakeHttpFetcher $http): GoogleTokenVerifier
    {
        return new GoogleTokenVerifier($http, self::CLIENT_ID, 'https://tokeninfo.test', new NullLogger());
    }

    private function tokeninfo(array $overrides = []): string
    {
        $payload = array_merge([
            'aud'            => self::CLIENT_ID,
            'iss'            => 'https://accounts.google.com',
            'sub'            => 'google-sub-999',
            'email'          => 'Asha@Example.com',
            'email_verified' => 'true',
            'name'           => 'Asha',
            'picture'        => 'https://pic',
        ], $overrides);

        return (string) json_encode($payload);
    }

    public function testVerifiesValidToken(): void
    {
        $claims = $this->verifier(new FakeHttpFetcher(200, $this->tokeninfo()))->verify('id-token');

        self::assertSame('google-sub-999', $claims['sub']);
        self::assertSame('asha@example.com', $claims['email']); // normalised
        self::assertTrue($claims['email_verified']);
        self::assertSame('Asha', $claims['name']);
    }

    public function testRejectsAudienceMismatch(): void
    {
        $http = new FakeHttpFetcher(200, $this->tokeninfo(['aud' => 'someone-else']));

        $this->expectException(UnauthorizedException::class);
        $this->verifier($http)->verify('id-token');
    }

    public function testRejectsBadIssuer(): void
    {
        $http = new FakeHttpFetcher(200, $this->tokeninfo(['iss' => 'evil.com']));

        $this->expectException(UnauthorizedException::class);
        $this->verifier($http)->verify('id-token');
    }

    public function testRejectsNon200Response(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->verifier(new FakeHttpFetcher(400, '{"error":"invalid"}'))->verify('id-token');
    }

    public function testRejectsMissingSubject(): void
    {
        $http = new FakeHttpFetcher(200, $this->tokeninfo(['sub' => '']));

        $this->expectException(UnauthorizedException::class);
        $this->verifier($http)->verify('id-token');
    }

    public function testRejectsWhenClientIdNotConfigured(): void
    {
        $verifier = new GoogleTokenVerifier(
            new FakeHttpFetcher(200, $this->tokeninfo()),
            '',
            'https://tokeninfo.test',
            new NullLogger()
        );

        $this->expectException(UnauthorizedException::class);
        $verifier->verify('id-token');
    }
}
