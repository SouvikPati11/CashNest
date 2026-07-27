<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Security;
use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    public function testPasswordHashAndVerify(): void
    {
        $hash = Security::hashPassword('S3cret!pass');

        self::assertNotSame('S3cret!pass', $hash);
        self::assertTrue(Security::verifyPassword('S3cret!pass', $hash));
        self::assertFalse(Security::verifyPassword('wrong', $hash));
    }

    public function testHmacRoundTrip(): void
    {
        $signature = Security::hmac('payload', 'secret');

        self::assertTrue(Security::verifyHmac('payload', 'secret', $signature));
        self::assertFalse(Security::verifyHmac('payload', 'secret', 'tampered'));
    }

    public function testUuid4Format(): void
    {
        $uuid = Security::uuid4();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function testNumericOtpLength(): void
    {
        $otp = Security::numericOtp(6);

        self::assertSame(6, strlen($otp));
        self::assertMatchesRegularExpression('/^\d{6}$/', $otp);
    }

    public function testAllowedUrlSchemes(): void
    {
        self::assertTrue(Security::isAllowedUrl('https://cashnest.app/x'));
        self::assertTrue(Security::isAllowedUrl('cashnest://spin'));
        self::assertFalse(Security::isAllowedUrl('javascript:alert(1)'));
    }
}
