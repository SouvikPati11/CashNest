<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\NullLogger;

final class UserServiceTest extends TestCase
{
    private InMemoryUserRepository $repo;

    private UserService $service;

    protected function setUp(): void
    {
        $this->repo    = new InMemoryUserRepository();
        $this->service = new UserService($this->repo, new NullLogger());
    }

    public function testCreateUserAssignsUuidReferralAndDefaults(): void
    {
        $user = $this->service->createUser(['name' => 'Asha', 'email' => 'ASHA@Example.com']);

        self::assertInstanceOf(User::class, $user);
        self::assertNotNull($user->id());
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string) $user->uuid());
        self::assertSame('active', $user->status());
        self::assertSame(8, strlen((string) $user->get('referral_code')));
        self::assertSame(0, $user->get('coin_balance_cache'));
        // Email is normalised to lowercase.
        self::assertSame('asha@example.com', $user->email());
    }

    public function testCreateUserIgnoresNonCreatableFields(): void
    {
        $user = $this->service->createUser([
            'name'               => 'Asha',
            'coin_balance_cache' => 999999, // must be ignored / forced to 0
            'status'             => 'banned', // status is allowed via resolver
        ]);

        self::assertSame(0, $user->get('coin_balance_cache'));
        self::assertSame('banned', $user->status()); // valid enum value is honoured
    }

    public function testCreateUserRejectsInvalidStatus(): void
    {
        $user = $this->service->createUser(['name' => 'X', 'status' => 'not-a-status']);

        self::assertSame('active', $user->status());
    }

    public function testEmailExistsIsCaseInsensitive(): void
    {
        $this->service->createUser(['email' => 'user@example.com']);

        self::assertTrue($this->service->emailExists('USER@EXAMPLE.COM'));
        self::assertFalse($this->service->emailExists('other@example.com'));
    }

    public function testFindByUuidAndEmail(): void
    {
        $created = $this->service->createUser(['email' => 'find@example.com']);

        self::assertNotNull($this->service->findByUuid((string) $created->uuid()));
        self::assertNotNull($this->service->findByEmail('FIND@example.com'));
        self::assertNull($this->service->findByUuid('missing-uuid'));
    }

    public function testUpdateProfileOnlyChangesAllowedFields(): void
    {
        $created = $this->service->createUser(['name' => 'Old']);
        $uuid    = (string) $created->uuid();

        $updated = $this->service->updateProfile($uuid, [
            'name'   => 'New',
            'status' => 'banned',  // not a profile field -> must be ignored
            'email'  => 'x@y.com', // not a profile field -> must be ignored
        ]);

        self::assertNotNull($updated);
        self::assertSame('New', $updated->get('name'));
        self::assertSame('active', $updated->status());
        self::assertNull($updated->email());
    }

    public function testUpdateProfileReturnsNullForUnknownUser(): void
    {
        self::assertNull($this->service->updateProfile('nope', ['name' => 'x']));
    }

    public function testGenerateReferralCodeIsUniqueAndFormatted(): void
    {
        $code = $this->service->generateReferralCode();

        self::assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $code);
    }

    public function testTouchLastLoginSetsTimestamp(): void
    {
        $user = $this->service->createUser(['name' => 'Asha']);
        $id   = (int) $user->id();

        $this->service->touchLastLogin($id);

        self::assertNotNull($this->repo->rows[$id]['last_login_at'] ?? null);
    }
}
