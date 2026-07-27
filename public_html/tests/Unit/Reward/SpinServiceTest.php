<?php

declare(strict_types=1);

namespace Tests\Unit\Reward;

use App\Exceptions\HttpException;
use App\Services\LedgerService;
use App\Services\SpinService;
use App\Services\WeightedPicker;
use Core\Config;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\FixedRandomizer;
use Tests\Support\InMemorySpinRepository;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\NullLogger;

final class SpinServiceTest extends TestCase
{
    private const USER = 12;

    private InMemorySpinRepository $spins;

    private InMemoryWalletRepository $wallets;

    private InMemoryWalletTransactionRepository $ledgerRepo;

    private FixedRandomizer $random;

    private SpinService $service;

    protected function setUp(): void
    {
        $this->spins      = new InMemorySpinRepository();
        $this->wallets    = new InMemoryWalletRepository();
        $this->ledgerRepo = new InMemoryWalletTransactionRepository();
        $this->random     = new FixedRandomizer(1);

        $this->spins->segments = [
            ['id' => 1, 'label' => '50', 'reward_type' => 'coins', 'reward_coins' => 50, 'weight' => 1, 'color_hex' => '#FFCC00', 'position' => 0],
            ['id' => 2, 'label' => 'Nothing', 'reward_type' => 'nothing', 'reward_coins' => 0, 'weight' => 1, 'color_hex' => '#CCC', 'position' => 1],
        ];

        $ledger = new LedgerService(new FakeTransactionRunner(), $this->wallets, $this->ledgerRepo, new NullLogger());
        $config = new Config(['rewards' => ['spin' => ['daily_limit' => 3]]]);

        $this->service = new SpinService($this->spins, $ledger, new WeightedPicker($this->random), $config, new NullLogger());
    }

    public function testCoinSpinCreditsThroughLedger(): void
    {
        $this->random->set(1); // first segment (50 coins)
        $result = $this->service->spin(self::USER, 'free');

        self::assertSame(1, $result['segment_id']);
        self::assertSame(50, $result['reward_coins']);
        self::assertSame(50, $result['new_balance']);
        self::assertCount(1, $this->ledgerRepo->rows);
        self::assertSame('spin', $this->ledgerRepo->rows[1]['type']);
    }

    public function testNothingSegmentDoesNotCredit(): void
    {
        $this->random->set(2); // second segment (nothing)
        $result = $this->service->spin(self::USER, 'free');

        self::assertSame(2, $result['segment_id']);
        self::assertSame(0, $result['reward_coins']);
        self::assertNull($result['transaction_uuid']);
        self::assertCount(0, $this->ledgerRepo->rows);
        // The spin is still recorded (counts toward the daily limit).
        self::assertCount(1, $this->spins->spins);
    }

    public function testDailyLimitEnforced(): void
    {
        $this->random->set(2); // nothing, so no credit noise
        $this->service->spin(self::USER, 'free');
        $this->service->spin(self::USER, 'free');
        $this->service->spin(self::USER, 'free');

        try {
            $this->service->spin(self::USER, 'free');
            self::fail('Expected LIMIT_REACHED.');
        } catch (HttpException $e) {
            self::assertSame(429, $e->getStatusCode());
            self::assertSame('LIMIT_REACHED', $e->getErrorCode());
        }
    }

    public function testStatusReportsRemainingAndSegmentsWithoutOdds(): void
    {
        $status = $this->service->status(self::USER);

        self::assertSame(3, $status['spins_remaining']);
        self::assertSame(3, $status['daily_limit']);
        self::assertCount(2, $status['segments']);
        // Weights / reward amounts are never exposed.
        self::assertArrayNotHasKey('weight', $status['segments'][0]);
        self::assertArrayNotHasKey('reward_coins', $status['segments'][0]);
    }
}
