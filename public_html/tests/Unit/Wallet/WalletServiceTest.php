<?php

declare(strict_types=1);

namespace Tests\Unit\Wallet;

use App\Exceptions\ValidationException;
use App\Services\LedgerService;
use App\Services\WalletService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryCurrencyRepository;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\NullLogger;

final class WalletServiceTest extends TestCase
{
    private const USER = 3;

    private InMemoryWalletRepository $wallets;

    private InMemoryWalletTransactionRepository $ledger;

    private LedgerService $ledgerService;

    private WalletService $service;

    protected function setUp(): void
    {
        $this->wallets = new InMemoryWalletRepository();
        $this->ledger  = new InMemoryWalletTransactionRepository();

        $this->ledgerService = new LedgerService(
            new FakeTransactionRunner(),
            $this->wallets,
            $this->ledger,
            new NullLogger()
        );

        $this->service = new WalletService(
            $this->wallets,
            $this->ledger,
            new InMemoryCurrencyRepository()
        );
    }

    public function testBalanceIsZeroWhenNoWallet(): void
    {
        $balance = $this->service->getBalance(self::USER);

        self::assertSame(0, $balance['coin_balance']);
        self::assertSame(0, $balance['available']);
        self::assertSame('0.0000', $balance['cash_balance']);
        self::assertSame('INR', $balance['currency']);
    }

    public function testBalanceReflectsLedger(): void
    {
        $this->ledgerService->credit(self::USER, 4200, 'offerwall', 'offerwall', 'r1');
        $this->ledgerService->reserve(self::USER, 200, 'withdraw', 'hold1');

        $balance = $this->service->getBalance(self::USER);

        self::assertSame(4000, $balance['coin_balance']);
        self::assertSame(4000, $balance['available']);
        self::assertSame(200, $balance['coin_reserved']);
        self::assertSame(4200, $balance['lifetime_earned']);
    }

    public function testConversionReturnsSettings(): void
    {
        $conversion = $this->service->getConversion();

        self::assertSame('0.00100000', $conversion['coin_to_cash_rate']);
        self::assertSame('INR', $conversion['currency']);
        self::assertSame(5000, $conversion['min_withdraw_coins']);
        self::assertSame(100000, $conversion['max_withdraw_coins']);
    }

    public function testHistoryReturnsNewestFirstWithCursor(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->ledgerService->credit(self::USER, $i * 10, 'spin', 'spin', 'r' . $i);
        }

        $page = $this->service->transactionHistory(self::USER, ['limit' => 2]);

        self::assertCount(2, $page['items']);
        self::assertTrue($page['has_more']);
        self::assertNotNull($page['next_cursor']);
        // Newest first: last created (r5, amount 50) leads.
        self::assertSame(50, $page['items'][0]->amount());
    }

    public function testHistoryCursorAdvances(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->ledgerService->credit(self::USER, $i, 'spin', 'spin', 'r' . $i);
        }

        $first  = $this->service->transactionHistory(self::USER, ['limit' => 2]);
        $second = $this->service->transactionHistory(self::USER, ['limit' => 2, 'cursor' => $first['next_cursor']]);

        $firstIds  = array_map(static fn($t) => $t->id(), $first['items']);
        $secondIds = array_map(static fn($t) => $t->id(), $second['items']);

        self::assertSame([], array_intersect($firstIds, $secondIds));
    }

    public function testHistoryFiltersByType(): void
    {
        $this->ledgerService->credit(self::USER, 10, 'spin', 'spin', 'r1');
        $this->ledgerService->credit(self::USER, 20, 'offerwall', 'offerwall', 'r2');

        $page = $this->service->transactionHistory(self::USER, ['type' => 'offerwall']);

        self::assertCount(1, $page['items']);
        self::assertSame('offerwall', $page['items'][0]->get('type'));
    }

    public function testHistoryRejectsRangeOverOneYear(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->transactionHistory(self::USER, [
            'date_from' => '2020-01-01',
            'date_to'   => '2024-01-01',
        ]);
    }

    public function testHistoryRejectsInvalidDate(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->transactionHistory(self::USER, ['date_from' => 'not-a-date']);
    }

    public function testFindTransactionForUserEnforcesOwnership(): void
    {
        $txn = $this->ledgerService->credit(self::USER, 10, 'spin', 'spin', 'r1');
        $uuid = (string) $txn->uuid();

        self::assertNotNull($this->service->findTransactionForUser($uuid, self::USER));
        self::assertNull($this->service->findTransactionForUser($uuid, 999));
    }
}
