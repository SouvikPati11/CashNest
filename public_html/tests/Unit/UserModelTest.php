<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

final class UserModelTest extends TestCase
{
    public function testCastsIdsAndBalances(): void
    {
        $user = User::fromRow([
            'id'                 => '5',
            'referred_by'        => '3',
            'coin_balance_cache' => '4200',
            'status'             => 'active',
        ]);

        self::assertSame(5, $user->id());
        self::assertSame(3, $user->get('referred_by'));
        self::assertSame(4200, $user->get('coin_balance_cache'));
    }

    public function testStatusHelpers(): void
    {
        $active = User::fromRow(['status' => 'active']);
        $banned = User::fromRow(['status' => 'banned']);

        self::assertTrue($active->isActive());
        self::assertFalse($active->isBlocked());
        self::assertTrue($banned->isBlocked());
        self::assertFalse($banned->isActive());
    }

    public function testVerifiedEmailHelper(): void
    {
        $unverified = User::fromRow(['email_verified_at' => null]);
        $verified   = User::fromRow(['email_verified_at' => '2026-01-01 00:00:00']);

        self::assertFalse($unverified->hasVerifiedEmail());
        self::assertTrue($verified->hasVerifiedEmail());
    }

    public function testMassAssignmentRespectsFillable(): void
    {
        $user = new User(['name' => 'Asha', 'id' => 99]);

        // id is not fillable, so it must not be mass-assigned.
        self::assertSame('Asha', $user->get('name'));
        self::assertNull($user->id());
    }

    public function testStatusesConstantMatchesSchemaEnum(): void
    {
        self::assertSame(['active', 'suspended', 'banned', 'deleted'], User::STATUSES);
    }
}
