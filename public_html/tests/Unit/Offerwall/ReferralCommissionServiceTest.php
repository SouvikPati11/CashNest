<?php

declare(strict_types=1);

namespace Tests\Unit\Offerwall;

use App\Services\LedgerService;
use App\Services\ReferralCommissionService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryReferralRepository;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\NullLogger;

final class ReferralCommissionServiceTest extends TestCase
{
    private const REFERRER = 100;
    private const REFEREE  = 200;

    private InMemoryReferralRepository $referrals;

    private InMemoryWalletRepository $wallets;

    private InMemoryWalletTransactionRepository $ledgerRepo;

    private LedgerService $ledger;

    private ReferralCommissionService $service;

    protected function setUp(): void
    {
        $this->referrals  = new InMemoryReferralRepository(); // referrer bonus 500, referee 100, 10% commission
        $this->wallets    = new InMemoryWalletRepository();
        $this->ledgerRepo = new InMemoryWalletTransactionRepository();
        $this->ledger     = new LedgerService(new FakeTransactionRunner(), $this->wallets, $this->ledgerRepo, new NullLogger());
        $this->service    = new ReferralCommissionService($this->referrals, $this->ledger, new NullLogger());

        $this->referrals->createReferral([
            'referrer_id'         => self::REFERRER,
            'referee_id'          => self::REFEREE,
            'referral_code'       => 'CODE',
            'status'              => 'pending',
            'signup_bonus_coins'  => 500,
            'referee_bonus_coins' => 100,
        ]);
    }

    public function testFirstEarnPaysBonusesAndCommissionThroughLedger(): void
    {
        $earning = $this->ledger->credit(self::REFEREE, 1000, 'offerwall', 'offerwall', 'offerwall:x:1');

        $this->service->applyForEarning(self::REFEREE, $earning);

        // Referrer: 500 signup bonus + 100 commission (10% of 1000) = 600.
        self::assertSame(600, (int) $this->wallets->findByUserId(self::REFERRER)['coin_balance']);
        // Referee: 1000 earning + 100 signup bonus = 1100.
        self::assertSame(1100, (int) $this->wallets->findByUserId(self::REFEREE)['coin_balance']);

        // Referral is now rewarded; one earning recorded.
        self::assertSame('rewarded', $this->referrals->referrals[1]['status']);
        self::assertCount(1, $this->referrals->earnings);

        // All credits flowed through the ledger: earning + 2 bonuses + commission.
        self::assertCount(4, $this->ledgerRepo->rows);
    }

    public function testCommissionIsIdempotentPerSourceTransaction(): void
    {
        $earning = $this->ledger->credit(self::REFEREE, 1000, 'offerwall', 'offerwall', 'offerwall:x:1');

        $this->service->applyForEarning(self::REFEREE, $earning);
        $this->service->applyForEarning(self::REFEREE, $earning); // replay

        self::assertCount(1, $this->referrals->earnings);
        self::assertSame(600, (int) $this->wallets->findByUserId(self::REFERRER)['coin_balance']);
    }

    public function testSubsequentEarningsAccrueMoreCommissionOnly(): void
    {
        $first = $this->ledger->credit(self::REFEREE, 1000, 'offerwall', 'offerwall', 'offerwall:x:1');
        $this->service->applyForEarning(self::REFEREE, $first);

        $second = $this->ledger->credit(self::REFEREE, 500, 'offerwall', 'offerwall', 'offerwall:x:2');
        $this->service->applyForEarning(self::REFEREE, $second);

        // Referrer: 600 + 50 (10% of 500) = 650. No second signup bonus.
        self::assertSame(650, (int) $this->wallets->findByUserId(self::REFERRER)['coin_balance']);
        self::assertCount(2, $this->referrals->earnings);
    }

    public function testNonReferredUserEarningDoesNothing(): void
    {
        $earning = $this->ledger->credit(999, 1000, 'offerwall', 'offerwall', 'offerwall:x:9');

        $this->service->applyForEarning(999, $earning);

        // Only the earning row exists; no commission/bonus.
        self::assertCount(1, $this->ledgerRepo->rows);
        self::assertCount(0, $this->referrals->earnings);
    }
}
