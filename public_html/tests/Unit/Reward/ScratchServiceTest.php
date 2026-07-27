<?php

declare(strict_types=1);

namespace Tests\Unit\Reward;

use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Services\LedgerService;
use App\Services\ScratchService;
use App\Services\WeightedPicker;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\FixedRandomizer;
use Tests\Support\InMemoryScratchRepository;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\NullLogger;

final class ScratchServiceTest extends TestCase
{
    private const USER = 8;

    private InMemoryScratchRepository $cards;

    private InMemoryWalletRepository $wallets;

    private InMemoryWalletTransactionRepository $ledgerRepo;

    private FixedRandomizer $random;

    private ScratchService $service;

    protected function setUp(): void
    {
        $this->cards      = new InMemoryScratchRepository();
        $this->wallets    = new InMemoryWalletRepository();
        $this->ledgerRepo = new InMemoryWalletTransactionRepository();
        $this->random     = new FixedRandomizer(1);

        $this->cards->pool = [
            ['reward_coins' => 50, 'weight' => 1, 'daily_limit' => null],
            ['reward_coins' => 100, 'weight' => 1, 'daily_limit' => null],
        ];

        $ledger = new LedgerService(new FakeTransactionRunner(), $this->wallets, $this->ledgerRepo, new NullLogger());
        $this->service = new ScratchService($this->cards, $ledger, new WeightedPicker($this->random), new NullLogger());
    }

    public function testRevealPicksRewardWithoutCrediting(): void
    {
        $cardId = $this->cards->seedCard(['user_id' => self::USER, 'status' => 'issued']);

        $this->random->set(1); // lands in the first tier (50)
        $result = $this->service->reveal(self::USER, $cardId);

        self::assertSame('revealed', $result['status']);
        self::assertSame(50, $result['reward_coins']);
        self::assertCount(0, $this->ledgerRepo->rows); // no credit at reveal
    }

    public function testRevealIsIdempotent(): void
    {
        $cardId = $this->cards->seedCard(['user_id' => self::USER, 'status' => 'issued']);

        $first  = $this->service->reveal(self::USER, $cardId);
        $second = $this->service->reveal(self::USER, $cardId);

        self::assertSame($first['reward_coins'], $second['reward_coins']);
    }

    public function testClaimCreditsThroughLedger(): void
    {
        $cardId = $this->cards->seedCard(['user_id' => self::USER, 'status' => 'revealed', 'reward_coins' => 50]);

        $result = $this->service->claim(self::USER, $cardId);

        self::assertSame(50, $result['coins_awarded']);
        self::assertSame(50, $result['new_balance']);
        self::assertCount(1, $this->ledgerRepo->rows);
        self::assertSame('scratch', $this->ledgerRepo->rows[1]['type']);
        self::assertSame('claimed', $this->cards->cards[$cardId]['status']);
    }

    public function testCannotClaimUnrevealedCard(): void
    {
        $cardId = $this->cards->seedCard(['user_id' => self::USER, 'status' => 'issued']);

        $this->expectException(HttpException::class);
        $this->service->claim(self::USER, $cardId);
    }

    public function testCannotClaimTwice(): void
    {
        $cardId = $this->cards->seedCard(['user_id' => self::USER, 'status' => 'revealed', 'reward_coins' => 50]);
        $this->service->claim(self::USER, $cardId);

        try {
            $this->service->claim(self::USER, $cardId);
            self::fail('Expected RESOURCE_CONFLICT.');
        } catch (HttpException $e) {
            self::assertSame('RESOURCE_CONFLICT', $e->getErrorCode());
        }
    }

    public function testExpiredCardRejected(): void
    {
        $cardId = $this->cards->seedCard([
            'user_id'      => self::USER,
            'status'       => 'revealed',
            'reward_coins' => 50,
            'expires_at'   => gmdate('Y-m-d H:i:s', time() - 3600),
        ]);

        try {
            $this->service->claim(self::USER, $cardId);
            self::fail('Expected EXPIRED.');
        } catch (HttpException $e) {
            self::assertSame(410, $e->getStatusCode());
            self::assertSame('EXPIRED', $e->getErrorCode());
        }
    }

    public function testUnknownCardNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->reveal(self::USER, 999);
    }

    public function testCardOwnershipEnforced(): void
    {
        $cardId = $this->cards->seedCard(['user_id' => self::USER, 'status' => 'issued']);

        $this->expectException(NotFoundException::class);
        $this->service->reveal(999, $cardId); // different user
    }
}
