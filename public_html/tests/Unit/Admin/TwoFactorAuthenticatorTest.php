<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Security\TwoFactorAuthenticator;
use PHPUnit\Framework\TestCase;
use Tests\Support\TotpGenerator;

final class TwoFactorAuthenticatorTest extends TestCase
{
    private const SECRET = 'JBSWY3DPEHPK3PXP';

    private TwoFactorAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->authenticator = new TwoFactorAuthenticator();
    }

    public function testAcceptsCurrentCode(): void
    {
        $code = TotpGenerator::now(self::SECRET);

        self::assertTrue($this->authenticator->verify(self::SECRET, $code));
    }

    public function testRejectsWrongFormat(): void
    {
        self::assertFalse($this->authenticator->verify(self::SECRET, 'abc'));
        self::assertFalse($this->authenticator->verify(self::SECRET, '1234567'));
    }

    public function testRejectsEmptySecret(): void
    {
        $code = TotpGenerator::now(self::SECRET);

        self::assertFalse($this->authenticator->verify('', $code));
    }

    public function testRejectsTamperedCode(): void
    {
        $code    = TotpGenerator::now(self::SECRET);
        $first   = $code[0] === '0' ? '1' : '0';
        $tampered = $first . substr($code, 1);

        self::assertFalse($this->authenticator->verify(self::SECRET, $tampered, 0));
    }
}
