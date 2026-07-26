<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AuthProvider;
use App\Services\AuthenticationService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAuthenticationRepository;
use Tests\Support\NullLogger;

final class AuthenticationServiceTest extends TestCase
{
    private InMemoryAuthenticationRepository $repo;

    private AuthenticationService $service;

    protected function setUp(): void
    {
        $this->repo    = new InMemoryAuthenticationRepository();
        $this->service = new AuthenticationService($this->repo, new NullLogger());
    }

    public function testHashAndVerifyPassword(): void
    {
        $hash = $this->service->hashPassword('S3cret!pass');

        self::assertNotSame('S3cret!pass', $hash);
        self::assertTrue($this->service->verifyPassword('S3cret!pass', $hash));
        self::assertFalse($this->service->verifyPassword('wrong', $hash));
    }

    public function testCreateEmailProviderPersistsAndHidesHash(): void
    {
        $provider = $this->service->createProvider([
            'user_id'       => 10,
            'provider'      => 'email',
            'email'         => 'USER@Example.com',
            'password_hash' => 'hashed',
            'is_primary'    => true,
        ]);

        self::assertInstanceOf(AuthProvider::class, $provider);
        self::assertSame(10, $provider->userId());
        self::assertSame('user@example.com', $provider->get('email')); // normalised
        self::assertTrue($provider->get('is_primary'));
        self::assertArrayNotHasKey('password_hash', $provider->toArray());
    }

    public function testCreateProviderRejectsInvalidProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->createProvider(['user_id' => 1, 'provider' => 'facebook']);
    }

    public function testFindProviderByUid(): void
    {
        $this->service->createProvider([
            'user_id'      => 7,
            'provider'     => 'google',
            'provider_uid' => 'google-sub-123',
        ]);

        $found = $this->service->findProviderByUid('google', 'google-sub-123');

        self::assertNotNull($found);
        self::assertSame(7, $found->userId());
        self::assertNull($this->service->findProviderByUid('google', 'nope'));
    }

    public function testFindEmailProviderIsCaseInsensitive(): void
    {
        $this->service->createProvider([
            'user_id'  => 3,
            'provider' => 'email',
            'email'    => 'me@example.com',
        ]);

        self::assertNotNull($this->service->findEmailProvider('ME@EXAMPLE.COM'));
    }

    public function testFindUserProvider(): void
    {
        $this->service->createProvider(['user_id' => 42, 'provider' => 'email', 'email' => 'a@b.com']);

        self::assertNotNull($this->service->findUserProvider(42, 'email'));
        self::assertNull($this->service->findUserProvider(42, 'google'));
    }

    public function testMarkProviderUsedSetsTimestamp(): void
    {
        $provider = $this->service->createProvider(['user_id' => 1, 'provider' => 'email', 'email' => 'a@b.com']);
        $id       = (int) $provider->get('id');

        $this->service->markProviderUsed($id);

        self::assertNotNull($this->repo->rows[$id]['last_used_at'] ?? null);
    }
}
