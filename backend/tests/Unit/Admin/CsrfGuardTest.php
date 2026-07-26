<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Security\CsrfGuard;
use PHPUnit\Framework\TestCase;
use Tests\Support\ArraySession;

final class CsrfGuardTest extends TestCase
{
    private CsrfGuard $csrf;

    protected function setUp(): void
    {
        $this->csrf = new CsrfGuard(new ArraySession());
    }

    public function testTokenIsStableWithinSession(): void
    {
        self::assertSame($this->csrf->token(), $this->csrf->token());
    }

    public function testValidatesMatchingToken(): void
    {
        $token = $this->csrf->token();

        self::assertTrue($this->csrf->validate($token));
        self::assertFalse($this->csrf->validate('wrong'));
        self::assertFalse($this->csrf->validate(null));
    }

    public function testRotateChangesToken(): void
    {
        $first = $this->csrf->token();
        $this->csrf->rotate();

        self::assertNotSame($first, $this->csrf->token());
        self::assertFalse($this->csrf->validate($first));
    }
}
