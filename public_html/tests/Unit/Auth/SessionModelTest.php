<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Models\Session;
use PHPUnit\Framework\TestCase;

final class SessionModelTest extends TestCase
{
    public function testHidesRefreshHash(): void
    {
        $session = Session::fromRow([
            'id'                 => 1,
            'user_id'            => 5,
            'refresh_token_hash' => 'secret-hash',
            'expires_at'         => '2099-01-01 00:00:00',
        ]);

        self::assertArrayNotHasKey('refresh_token_hash', $session->toArray());
        self::assertSame(5, $session->userId());
    }

    public function testIsRevoked(): void
    {
        $active  = Session::fromRow(['revoked_at' => null]);
        $revoked = Session::fromRow(['revoked_at' => '2026-01-01 00:00:00']);

        self::assertFalse($active->isRevoked());
        self::assertTrue($revoked->isRevoked());
    }

    public function testIsExpired(): void
    {
        $future = Session::fromRow(['expires_at' => gmdate('Y-m-d H:i:s', time() + 3600)]);
        $past   = Session::fromRow(['expires_at' => gmdate('Y-m-d H:i:s', time() - 3600)]);

        self::assertFalse($future->isExpired(time()));
        self::assertTrue($past->isExpired(time()));
    }

    public function testMissingExpiryIsTreatedAsExpired(): void
    {
        $session = Session::fromRow(['user_id' => 1]);

        self::assertTrue($session->isExpired(time()));
    }
}
