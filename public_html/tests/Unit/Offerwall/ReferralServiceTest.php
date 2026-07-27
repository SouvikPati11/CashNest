<?php

declare(strict_types=1);

namespace Tests\Unit\Offerwall;

use App\Exceptions\ForbiddenException;
use App\Exceptions\HttpException;
use App\Exceptions\ValidationException;
use App\Services\ReferralService;
use App\Services\UserService;
use Core\Config;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryReferralRepository;
use Tests\Support\InMemoryUserRepository;
use Tests\Support\NullLogger;

final class ReferralServiceTest extends TestCase
{
    private InMemoryReferralRepository $referrals;

    private InMemoryUserRepository $users;

    private ReferralService $service;

    private int $referrerId;

    protected function setUp(): void
    {
        $this->referrals = new InMemoryReferralRepository();
        $this->users     = new InMemoryUserRepository();

        $userService = new UserService($this->users, new NullLogger());

        // Seed a referrer with a known code.
        $this->referrerId = (int) $this->users->create([
            'uuid' => 'u-ref', 'name' => 'Referrer', 'referral_code' => 'REF12345', 'status' => 'active',
        ]);

        $this->service = new ReferralService(
            $this->referrals,
            $userService,
            $this->users,
            new Config(['app' => ['url' => 'https://cashnest.app']]),
            new NullLogger()
        );
    }

    public function testApplyCreatesReferral(): void
    {
        $refereeId = (int) $this->users->create(['uuid' => 'u-new', 'referral_code' => 'NEWCODE1', 'status' => 'active']);

        $result = $this->service->apply($refereeId, 'REF12345', '1.2.3.4');

        self::assertTrue($result['applied']);
        self::assertNotNull($this->referrals->findByReferee($refereeId));
        self::assertSame($this->referrerId, (int) $this->referrals->findByReferee($refereeId)['referrer_id']);
    }

    public function testCannotApplyOwnCode(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->service->apply($this->referrerId, 'REF12345', null);
    }

    public function testCannotApplyTwice(): void
    {
        $refereeId = (int) $this->users->create(['uuid' => 'u-2', 'referral_code' => 'CODE0002', 'status' => 'active']);
        $this->service->apply($refereeId, 'REF12345', null);

        try {
            $this->service->apply($refereeId, 'REF12345', null);
            self::fail('Expected RESOURCE_CONFLICT.');
        } catch (HttpException $e) {
            self::assertSame(409, $e->getStatusCode());
            self::assertSame('RESOURCE_CONFLICT', $e->getErrorCode());
        }
    }

    public function testInvalidCodeRejected(): void
    {
        $refereeId = (int) $this->users->create(['uuid' => 'u-3', 'referral_code' => 'CODE0003', 'status' => 'active']);

        $this->expectException(ValidationException::class);
        $this->service->apply($refereeId, 'DOESNOTEXIST', null);
    }

    public function testOverviewReturnsCodeLinkAndStats(): void
    {
        $overview = $this->service->overview($this->referrerId);

        self::assertSame('REF12345', $overview['referral_code']);
        self::assertSame('https://cashnest.app/r/REF12345', $overview['referral_link']);
        self::assertSame(0, $overview['total_referrals']);
        self::assertSame('0.1000', $overview['commission_percent']);
    }
}
