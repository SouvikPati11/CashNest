<?php

declare(strict_types=1);

namespace Tests\Unit\Reward;

use App\Exceptions\HttpException;
use App\Services\CheckinService;
use App\Services\LedgerService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryCheckinRepository;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\NullLogger;

final class CheckinServiceTest extends TestCase
{
    private const USER = 5;

    private InMemoryCheckinRepository $checkins;

    private InMemoryWalletRepository $wallets;

    private InMemoryWalletTransactionRepository $ledgerRepo;

    private CheckinService $service;

    protected function setUp(): void
    {
        $this->checkins   = new InMemoryCheckinRepository();
        $this->wallets    = new InMemoryWalletRepository();
        $this->ledgerRepo = new InMemoryWalletTransactionRepository();

        $this->checkins->ladder = [
            ['day_number' => 1, 'coins' => 10, 'is_milestone' => 0],
            ['day_number' => 2, 'coins' => 15, 'is_milestone' => 0],
            ['day_number' => 3, 'coins' => 30, 'is_milestone' => 1],
        ];

        $ledger = new LedgerService(new FakeTransactionRunner(), $this->wallets, $this->ledgerRepo, new NullLogger());
        $this->service = new CheckinService($this->checkins, $ledger, new NullLogger());
    }

    public function testClaimCreditsThroughLedger(): void
    {
        $result = $this->service->claim(self::USER);

        self::assertSame(10, $result['coins_awarded']);
        self::assertSame(1, $result['streak_day']);
        self::assertSame(10, $result['new_balance']);
        self::assertNotNull($result['transaction_uuid']);

        // Credit went through the ledger.
        self::assertCount(1, $this->ledgerRepo->rows);
        self::assertSame('checkin', $this->ledgerRepo->rows[1]['type']);
        self::assertSame(10, (int) $this->wallets->findByUserId(self::USER)['coin_balance']);
    }

    public function testCannotClaimTwiceSameDay(): void
    {
        $this->service->claim(self::USER);

        try {
            $this->service->claim(self::USER);
            self::fail('Expected ALREADY_CLAIMED.');
        } catch (HttpException $e) {
            self::assertSame(409, $e->getStatusCode());
            self::assertSame('ALREADY_CLAIMED', $e->getErrorCode());
        }

        self::assertCount(1, $this->ledgerRepo->rows); // no second credit
    }

    public function testStreakContinuesFromYesterday(): void
    {
        $yesterday = gmdate('Y-m-d', strtotime('-1 day'));
        $this->checkins->create([
            'user_id'      => self::USER,
            'checkin_date' => $yesterday,
            'streak_day'   => 1,
            'coins_awarded' => 10,
        ]);

        $result = $this->service->claim(self::USER);

        self::assertSame(2, $result['streak_day']);
        self::assertSame(15, $result['coins_awarded']);
    }

    public function testStreakResetsAfterGap(): void
    {
        $threeDaysAgo = gmdate('Y-m-d', strtotime('-3 day'));
        $this->checkins->create([
            'user_id'      => self::USER,
            'checkin_date' => $threeDaysAgo,
            'streak_day'   => 5,
            'coins_awarded' => 50,
        ]);

        $result = $this->service->claim(self::USER);

        self::assertSame(1, $result['streak_day']); // reset
    }

    public function testStatusReportsClaimability(): void
    {
        $status = $this->service->status(self::USER);
        self::assertTrue($status['can_claim_today']);
        self::assertSame(0, $status['current_streak']);
        self::assertSame(10, $status['next_reward_coins']);

        $this->service->claim(self::USER);

        $after = $this->service->status(self::USER);
        self::assertFalse($after['can_claim_today']);
        self::assertSame(1, $after['current_streak']);
    }

    public function testCalendarReturnsLadder(): void
    {
        $calendar = $this->service->calendar(self::USER);

        self::assertCount(3, $calendar['ladder']);
        self::assertSame(30, $calendar['ladder'][2]['coins']);
        self::assertTrue($calendar['ladder'][2]['is_milestone']);
    }
}
