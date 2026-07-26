<?php

declare(strict_types=1);

namespace Tests\Unit\Withdraw;

use App\Exceptions\HttpException;
use App\Models\WithdrawRequest;
use App\Services\LedgerService;
use App\Services\WithdrawSettlementService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\InMemoryWithdrawRequestRepository;
use Tests\Support\NullLogger;

final class WithdrawSettlementServiceTest extends TestCase
{
    private const USER  = 7;
    private const COINS = 5000;

    private InMemoryWithdrawRequestRepository $requests;

    private InMemoryWalletRepository $wallets;

    private InMemoryWalletTransactionRepository $ledgerRepo;

    private LedgerService $ledger;

    private WithdrawSettlementService $service;

    private int $requestId;

    protected function setUp(): void
    {
        $this->requests   = new InMemoryWithdrawRequestRepository();
        $this->wallets    = new InMemoryWalletRepository();
        $this->ledgerRepo = new InMemoryWalletTransactionRepository();
        $this->ledger     = new LedgerService(
            new FakeTransactionRunner(),
            $this->wallets,
            $this->ledgerRepo,
            new NullLogger()
        );
        $this->service = new WithdrawSettlementService($this->requests, $this->ledger, new NullLogger());

        // Wallet starts with a hold in place (as if a request was just made).
        $this->wallets->create(['user_id' => self::USER, 'coin_balance' => 10000]);
        $hold = $this->ledger->reserve(self::USER, self::COINS, 'withdraw', 'withdraw_hold:seed');

        $this->requestId = (int) $this->requests->create([
            'uuid'                => 'wr-1',
            'user_id'             => self::USER,
            'coins_amount'        => self::COINS,
            'net_amount'          => '5.0000',
            'status'              => WithdrawRequest::STATUS_PENDING,
            'hold_transaction_id' => (int) $hold->id(),
        ]);
    }

    public function testApproveThenPaySettlesThroughLedger(): void
    {
        $this->service->approve($this->requestId, 42);
        self::assertSame('approved', $this->requests->rows[$this->requestId]['status']);
        // Approval moves no coins; only the hold row exists so far.
        self::assertCount(1, $this->ledgerRepo->rows);

        $paid = $this->service->markPaid($this->requestId, 42, 'gw-ref-9');

        self::assertSame('paid', $paid->status());

        // Settlement = release (reserved → balance) + debit (balance → out).
        $wallet = $this->wallets->findByUserId(self::USER);
        self::assertSame(5000, (int) $wallet['coin_balance']);
        self::assertSame(0, (int) $wallet['coin_reserved']);
        self::assertSame(5000, (int) $wallet['lifetime_coins_spent']);

        self::assertCount(3, $this->ledgerRepo->rows); // hold + release + debit
        self::assertNotNull($this->requests->rows[$this->requestId]['debit_transaction_id']);
        self::assertSame('gw-ref-9', $this->requests->rows[$this->requestId]['external_reference']);
    }

    public function testRejectReleasesHold(): void
    {
        $rejected = $this->service->reject($this->requestId, 42, 'Suspicious.');

        self::assertSame('rejected', $rejected->status());

        $wallet = $this->wallets->findByUserId(self::USER);
        self::assertSame(10000, (int) $wallet['coin_balance']);
        self::assertSame(0, (int) $wallet['coin_reserved']);
        self::assertCount(2, $this->ledgerRepo->rows); // hold + release
        self::assertNotNull($this->requests->rows[$this->requestId]['refund_transaction_id']);
    }

    public function testCannotPayWithoutApproval(): void
    {
        try {
            $this->service->markPaid($this->requestId);
            self::fail('Expected RESOURCE_CONFLICT.');
        } catch (HttpException $e) {
            self::assertSame(409, $e->getStatusCode());
            self::assertSame('RESOURCE_CONFLICT', $e->getErrorCode());
        }
    }

    public function testApproveIsIdempotent(): void
    {
        $this->service->approve($this->requestId, 42);
        $this->service->approve($this->requestId, 42);

        self::assertCount(1, $this->requests->historyForRequest($this->requestId));
    }

    public function testPayIsIdempotent(): void
    {
        $this->service->approve($this->requestId, 42);
        $this->service->markPaid($this->requestId, 42);
        $this->service->markPaid($this->requestId, 42); // replay

        self::assertCount(3, $this->ledgerRepo->rows); // no extra release/debit
        self::assertSame(5000, (int) $this->wallets->findByUserId(self::USER)['coin_balance']);
    }

    public function testCannotRejectAfterPaid(): void
    {
        $this->service->approve($this->requestId, 42);
        $this->service->markPaid($this->requestId, 42);

        $this->expectException(HttpException::class);
        $this->service->reject($this->requestId, 42);
    }
}
