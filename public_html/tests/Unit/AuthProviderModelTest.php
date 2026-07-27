<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AuthProvider;
use PHPUnit\Framework\TestCase;

final class AuthProviderModelTest extends TestCase
{
    public function testPasswordHashIsHiddenFromSerialization(): void
    {
        $provider = AuthProvider::fromRow([
            'id'            => 1,
            'user_id'       => 10,
            'provider'      => 'email',
            'password_hash' => 'secret-hash',
            'email'         => 'a@b.com',
        ]);

        $array = $provider->toArray();

        self::assertArrayNotHasKey('password_hash', $array);
        self::assertArrayNotHasKey('password_hash', $provider->jsonSerialize());
        // ...but is still readable server-side via the accessor.
        self::assertSame('secret-hash', $provider->passwordHash());
    }

    public function testCasts(): void
    {
        $provider = AuthProvider::fromRow([
            'user_id'    => '10',
            'is_primary' => '1',
            'provider'   => 'google',
        ]);

        self::assertSame(10, $provider->userId());
        self::assertTrue($provider->get('is_primary'));
    }

    public function testProviderHelpers(): void
    {
        $email  = AuthProvider::fromRow(['provider' => 'email']);
        $google = AuthProvider::fromRow(['provider' => 'google']);

        self::assertTrue($email->isEmailProvider());
        self::assertFalse($google->isEmailProvider());
    }

    public function testProvidersConstantMatchesSchemaEnum(): void
    {
        self::assertSame(['google', 'email'], AuthProvider::PROVIDERS);
    }
}
