<?php

declare(strict_types=1);

namespace Tests\Unit\Withdraw;

use App\Exceptions\ForbiddenException;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Services\LedgerService;
use App\Services\WithdrawCalculator;
use App\Services\WithdrawService;
use Core\Config;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryCurrencyRepository;
use Tests\Support\InMemoryFraudFlagRepository;
use Tests\Support\InMemoryKycRepository;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\InMemoryWithdrawMethodRepository;
use Tests\Support\InMemoryWithdrawRequestRepository;
use Tests\Support\NullLogger;

final class WithdrawServiceTest extends TestCase
{
    private const USER = 1;

    private InMemoryWithdrawMethodRepository $methods;

    private InMemoryWithdrawRequestRepository $requests;

    private InMemoryWalletRepository $wallets;

    private InMemoryWalletTransactionRepository $ledgerRepo;

    private InMemoryKycRepository $kyc;

    private InMemoryFraudFlagRepository $fraud;

    private WithdrawService $service;

    protected function setUp(): void
    {
        $this->methods    = new InMemoryWithdrawMethodRepository();
        $this->requests   = new InMemoryWithdrawRequestRepository();
        $this->wallets    = new InMemoryWalletRepository();
        $this->ledgerRepo = new InMemoryWalletTransactionRepository();
        $this->kyc        = new InMemoryKycRepository();
        $this->fraud      = new InMemoryFraudFlagRepository();

        $ledger = new LedgerService(new FakeTransactionRunner(), $this->wallets, $this->ledgerRepo, new NullLogger());

        $this->methods->seed(['code' => 'upi', 'min_coins' => 5000, 'max_coins' => 100000]);

        $this->service = new WithdrawService(
            $this->methods,
            $this->requests,
            $this->wallets,
            $this->ledgerRepo,
            new InMemoryCurrencyRepository(),
            $this->kyc,
            $this->fraud,
            $ledger,
            new WithdrawCalculator(),
            new Config(['withdraw' => ['kyc_required_threshold_coins' => 50000]]),
            new NullLogger()
        );
    }

    private function seedWallet(int $balance): void
    {
        $this->wallets->create(['user_id' => self::USER, 'coin_balance' => $balance]);
    }

    /**
     * @param array<string, mixed> $detail
     * @return array<string, mixed>
     */
    private function requestWithdrawal(int $coins, string $key, array $detail = ['upi_id' => 'a@bank']): array
    {
        return $this->service->request(self::USER, 'upi', $coins, $detail, $key, '1.2.3.4');
    }

    public function testMethodsExposeLimitsButNoInternalIds(): void
    {
        $list = $this->service->methods();

        self::assertCount(1, $list);
        self::assertSame('upi', $list[0]['code']);
        self::assertSame(5000, $list[0]['min_coins']);
        self::assertArrayNotHasKey('id', $list[0]);
    }

    public function testRequestReservesCoinsThroughLedger(): void
    {
        $this->seedWallet(10000);

        $result = $this->requestWithdrawal(5000, 'idem-1');

        self::assertSame('pending', $result['status']);
        self::assertSame(5000, $result['coins_amount']);
        self::assertSame('5.0000', $result['cash_amount']);
        self::assertSame('5.0000', $result['net_amount']);
        self::assertNotNull($result['hold_transaction_uuid']);

        // Balance moved into reserve via the ledger, not mutated directly.
        $wallet = $this->wallets->findByUserId(self::USER);
        self::assertSame(5000, (int) $wallet['coin_balance']);
        self::assertSame(5000, (int) $wallet['coin_reserved']);

        self::assertCount(1, $this->requests->rows);
        self::assertCount(1, $this->ledgerRepo->rows);
        self::assertCount(1, $this->requests->history);
    }

    public function testRequestIsIdempotentOnKey(): void
    {
        $this->seedWallet(5000);

        $first  = $this->requestWithdrawal(5000, 'idem-dup');
        $second = $this->requestWithdrawal(5000, 'idem-dup');

        self::assertSame($first['uuid'], $second['uuid']);
        self::assertCount(1, $this->requests->rows);
        self::assertCount(1, $this->ledgerRepo->rows);

        // No double reserve: balance was reduced exactly once.
        self::assertSame(0, (int) $this->wallets->findByUserId(self::USER)['coin_balance']);
    }

    public function testRejectsBelowMinimum(): void
    {
        $this->seedWallet(10000);

        try {
            $this->requestWithdrawal(4000, 'idem-min');
            self::fail('Expected MIN_WITHDRAW_NOT_MET.');
        } catch (HttpException $e) {
            self::assertSame(422, $e->getStatusCode());
            self::assertSame('MIN_WITHDRAW_NOT_MET', $e->getErrorCode());
        }
    }

    public function testRejectsAboveMaximum(): void
    {
        $this->seedWallet(500000);

        $this->expectException(ValidationException::class);
        $this->requestWithdrawal(200000, 'idem-max');
    }

    public function testRejectsInsufficientBalance(): void
    {
        $this->seedWallet(4000); // above min is 5000, so balance is the blocker

        try {
            $this->requestWithdrawal(5000, 'idem-bal');
            self::fail('Expected INSUFFICIENT_BALANCE.');
        } catch (HttpException $e) {
            self::assertSame('INSUFFICIENT_BALANCE', $e->getErrorCode());
        }
    }

    public function testRejectsUnknownMethod(): void
    {
        $this->seedWallet(10000);

        $this->expectException(ValidationException::class);
        $this->service->request(self::USER, 'paypal', 5000, ['email' => 'a@b.c'], 'idem-x', null);
    }

    public function testRejectsMissingPaymentDetail(): void
    {
        $this->seedWallet(10000);

        $this->expectException(ValidationException::class);
        $this->requestWithdrawal(5000, 'idem-detail', []);
    }

    public function testKycRequiredAtOrAboveThreshold(): void
    {
        $this->seedWallet(100000);

        try {
            $this->requestWithdrawal(50000, 'idem-kyc');
            self::fail('Expected KYC_REQUIRED.');
        } catch (ForbiddenException $e) {
            self::assertSame('KYC_REQUIRED', $e->getErrorCode());
        }

        // With approved KYC the same request succeeds.
        $this->kyc->set(self::USER, 'approved');
        $result = $this->requestWithdrawal(50000, 'idem-kyc-2');
        self::assertSame('pending', $result['status']);
    }

    public function testFraudHoldBlocksRequest(): void
    {
        $this->seedWallet(10000);
        $this->fraud->hold(self::USER);

        try {
            $this->requestWithdrawal(5000, 'idem-fraud');
            self::fail('Expected FRAUD_HOLD.');
        } catch (ForbiddenException $e) {
            self::assertSame('FRAUD_HOLD', $e->getErrorCode());
        }
    }

    public function testCancelReleasesHold(): void
    {
        $this->seedWallet(10000);
        $created = $this->requestWithdrawal(5000, 'idem-cancel');

        $result = $this->service->cancel(self::USER, (string) $created['uuid']);

        self::assertSame('cancelled', $result['status']);
        self::assertSame(5000, $result['refunded_coins']);
        self::assertSame(10000, $result['new_balance']);

        $wallet = $this->wallets->findByUserId(self::USER);
        self::assertSame(10000, (int) $wallet['coin_balance']);
        self::assertSame(0, (int) $wallet['coin_reserved']);
        self::assertCount(2, $this->ledgerRepo->rows); // hold + release
    }

    public function testCancelIsIdempotent(): void
    {
        $this->seedWallet(10000);
        $created = $this->requestWithdrawal(5000, 'idem-cancel-2');

        $this->service->cancel(self::USER, (string) $created['uuid']);
        $again = $this->service->cancel(self::USER, (string) $created['uuid']);

        self::assertSame('cancelled', $again['status']);
        self::assertSame(0, $again['refunded_coins']);
        self::assertCount(2, $this->ledgerRepo->rows); // no extra release
    }

    public function testCancelNonPendingConflicts(): void
    {
        $this->seedWallet(10000);
        $created = $this->requestWithdrawal(5000, 'idem-cancel-3');
        $this->requests->updateRequest(1, ['status' => 'approved']);

        try {
            $this->service->cancel(self::USER, (string) $created['uuid']);
            self::fail('Expected RESOURCE_CONFLICT.');
        } catch (HttpException $e) {
            self::assertSame(409, $e->getStatusCode());
            self::assertSame('RESOURCE_CONFLICT', $e->getErrorCode());
        }
    }

    public function testDetailReturnsTimelineAndPaymentDetail(): void
    {
        $this->seedWallet(10000);
        $created = $this->requestWithdrawal(5000, 'idem-detail-2');

        $detail = $this->service->detail(self::USER, (string) $created['uuid']);

        self::assertSame('a@bank', $detail['payment_detail']['upi_id']);
        self::assertCount(1, $detail['history']);
        self::assertSame('pending', $detail['history'][0]['to_status']);
    }

    public function testDetailNotFoundForOtherUser(): void
    {
        $this->seedWallet(10000);
        $created = $this->requestWithdrawal(5000, 'idem-detail-3');

        $this->expectException(NotFoundException::class);
        $this->service->detail(999, (string) $created['uuid']);
    }

    public function testHistoryPaginates(): void
    {
        $this->seedWallet(20000);
        $this->requestWithdrawal(5000, 'idem-h1');
        $this->requestWithdrawal(5000, 'idem-h2');

        $page = $this->service->history(self::USER, ['limit' => 1]);

        self::assertCount(1, $page['items']);
        self::assertTrue($page['has_more']);
    }
}
