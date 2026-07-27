<?php

declare(strict_types=1);

namespace Tests\Unit\Wallet;

use App\Exceptions\HttpException;
use App\Models\WalletTransaction;
use App\Services\LedgerService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\NullLogger;

final class LedgerServiceTest extends TestCase
{
    private const USER = 7;

    private InMemoryWalletRepository $wallets;

    private InMemoryWalletTransactionRepository $ledger;

    private LedgerService $service;

    protected function setUp(): void
    {
        $this->wallets = new InMemoryWalletRepository();
        $this->ledger  = new InMemoryWalletTransactionRepository();
        $this->service = new LedgerService(
            new FakeTransactionRunner(),
            $this->wallets,
            $this->ledger,
            new NullLogger()
        );
    }

    private function wallet(): array
    {
        $row = $this->wallets->findByUserId(self::USER);
        self::assertNotNull($row);

        return $row;
    }

    public function testCreditCreatesWalletLedgerRowAndBalance(): void
    {
        $txn = $this->service->credit(self::USER, 100, 'checkin', 'checkin', 'checkin:7:2026-07-25');

        self::assertInstanceOf(WalletTransaction::class, $txn);
        self::assertSame('credit', $txn->direction());
        self::assertSame(100, $txn->amount());
        self::assertSame(100, (int) $txn->get('balance_after'));

        $wallet = $this->wallet();
        self::assertSame(100, (int) $wallet['coin_balance']);
        self::assertSame(100, (int) $wallet['lifetime_coins_earned']);
        self::assertSame(1, (int) $wallet['version']);
        self::assertSame($txn->id(), (int) $wallet['last_transaction_id']);
        self::assertCount(1, $this->ledger->rows);
    }

    public function testEveryCreditWritesExactlyOneLedgerRow(): void
    {
        $this->service->credit(self::USER, 50, 'spin', 'spin', 'ref-a');
        $this->service->credit(self::USER, 30, 'task', 'task', 'ref-b');

        self::assertCount(2, $this->ledger->rows);
        self::assertSame(80, (int) $this->wallet()['coin_balance']);
    }

    public function testCreditIsIdempotentByReferenceId(): void
    {
        $first  = $this->service->credit(self::USER, 100, 'offerwall', 'offerwall', 'offerwall:x:tx1');
        $second = $this->service->credit(self::USER, 100, 'offerwall', 'offerwall', 'offerwall:x:tx1');

        // Same ledger row returned; no double credit.
        self::assertSame($first->id(), $second->id());
        self::assertCount(1, $this->ledger->rows);
        self::assertSame(100, (int) $this->wallet()['coin_balance']);
    }

    public function testDebitReducesBalanceAndTracksSpent(): void
    {
        $this->service->credit(self::USER, 200, 'offerwall', 'offerwall', 'c1');
        $txn = $this->service->debit(self::USER, 120, 'adjustment', 'admin', 'd1');

        self::assertSame('debit', $txn->direction());
        self::assertSame(80, (int) $this->wallet()['coin_balance']);
        self::assertSame(120, (int) $this->wallet()['lifetime_coins_spent']);
    }

    public function testDebitBeyondBalanceIsRejected(): void
    {
        $this->service->credit(self::USER, 50, 'offerwall', 'offerwall', 'c1');

        try {
            $this->service->debit(self::USER, 100, 'adjustment', 'admin', 'd1');
            self::fail('Expected INSUFFICIENT_BALANCE.');
        } catch (HttpException $e) {
            self::assertSame(422, $e->getStatusCode());
            self::assertSame('INSUFFICIENT_BALANCE', $e->getErrorCode());
        }

        // Balance untouched and no ledger row written for the failed debit.
        self::assertSame(50, (int) $this->wallet()['coin_balance']);
        self::assertCount(1, $this->ledger->rows);
    }

    public function testReserveMovesCoinsToReservedAndReducesAvailable(): void
    {
        $this->service->credit(self::USER, 500, 'offerwall', 'offerwall', 'c1');
        $txn = $this->service->reserve(self::USER, 300, 'withdraw', 'withdraw_hold:1');

        self::assertSame('withdrawal_hold', $txn->get('type'));
        self::assertSame('debit', $txn->direction());

        $wallet = $this->wallet();
        self::assertSame(200, (int) $wallet['coin_balance']);  // available
        self::assertSame(300, (int) $wallet['coin_reserved']);
        self::assertSame(0, (int) $wallet['lifetime_coins_spent']); // holds are not spends
    }

    public function testReleaseReturnsReservedToBalance(): void
    {
        $this->service->credit(self::USER, 500, 'offerwall', 'offerwall', 'c1');
        $this->service->reserve(self::USER, 300, 'withdraw', 'hold:1');
        $txn = $this->service->release(self::USER, 300, 'withdraw', 'release:1');

        self::assertSame('withdrawal_release', $txn->get('type'));
        self::assertSame('credit', $txn->direction());

        $wallet = $this->wallet();
        self::assertSame(500, (int) $wallet['coin_balance']);
        self::assertSame(0, (int) $wallet['coin_reserved']);
    }

    public function testCannotReserveMoreThanAvailable(): void
    {
        $this->service->credit(self::USER, 100, 'offerwall', 'offerwall', 'c1');

        $this->expectException(HttpException::class);
        $this->service->reserve(self::USER, 200, 'withdraw', 'hold:1');
    }

    public function testCannotReleaseMoreThanReserved(): void
    {
        $this->service->credit(self::USER, 100, 'offerwall', 'offerwall', 'c1');

        $this->expectException(HttpException::class);
        $this->service->release(self::USER, 50, 'withdraw', 'release:1');
    }

    public function testRejectsNonPositiveAmount(): void
    {
        $this->expectException(HttpException::class);
        $this->service->credit(self::USER, 0, 'checkin', 'checkin', 'ref');
    }

    public function testRejectsInvalidType(): void
    {
        try {
            $this->service->credit(self::USER, 10, 'not-a-type', 'x', 'ref');
            self::fail('Expected VALIDATION_ERROR.');
        } catch (HttpException $e) {
            self::assertSame('VALIDATION_ERROR', $e->getErrorCode());
        }
    }

    public function testBalanceAfterMatchesRunningBalance(): void
    {
        $t1 = $this->service->credit(self::USER, 100, 'offerwall', 'offerwall', 'r1');
        $t2 = $this->service->credit(self::USER, 25, 'spin', 'spin', 'r2');
        $t3 = $this->service->debit(self::USER, 40, 'adjustment', 'admin', 'r3');

        self::assertSame(100, (int) $t1->get('balance_after'));
        self::assertSame(125, (int) $t2->get('balance_after'));
        self::assertSame(85, (int) $t3->get('balance_after'));
    }
}
