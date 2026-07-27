<?php

declare(strict_types=1);

namespace Tests\Unit\Wallet;

use App\Controllers\Wallet\WalletController;
use App\Controllers\Wallet\WalletTransactionController;
use App\Exceptions\NotFoundException;
use App\Services\LedgerService;
use App\Services\WalletService;
use Core\Http\Request;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryCurrencyRepository;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\NullLogger;

final class WalletControllersTest extends TestCase
{
    private const USER = 11;

    private WalletService $walletService;

    private LedgerService $ledgerService;

    protected function setUp(): void
    {
        $wallets = new InMemoryWalletRepository();
        $ledger  = new InMemoryWalletTransactionRepository();

        $this->ledgerService = new LedgerService(new FakeTransactionRunner(), $wallets, $ledger, new NullLogger());
        $this->walletService = new WalletService($wallets, $ledger, new InMemoryCurrencyRepository());
    }

    private function authed(string $method, string $path, array $query = []): Request
    {
        $request = new Request($method, $path, $query);
        $request->setAttribute('user_id', self::USER);

        return $request;
    }

    public function testBalanceEndpoint(): void
    {
        $this->ledgerService->credit(self::USER, 4200, 'offerwall', 'offerwall', 'r1');

        $response = (new WalletController($this->walletService))->balance($this->authed('GET', '/v1/wallet'));
        $payload  = json_decode($response->body(), true);

        self::assertSame(200, $response->status());
        self::assertSame(4200, $payload['data']['coin_balance']);
        self::assertSame(4200, $payload['data']['available']);
    }

    public function testConversionEndpoint(): void
    {
        $response = (new WalletController($this->walletService))->conversion($this->authed('GET', '/v1/wallet/conversion'));
        $payload  = json_decode($response->body(), true);

        self::assertSame('0.00100000', $payload['data']['coin_to_cash_rate']);
        self::assertSame(5000, $payload['data']['min_withdraw_coins']);
    }

    public function testTransactionsListEndpointHasPaginationMeta(): void
    {
        $this->ledgerService->credit(self::USER, 100, 'spin', 'spin', 'r1');

        $controller = new WalletTransactionController($this->walletService);
        $response   = $controller->index($this->authed('GET', '/v1/wallet/transactions', ['limit' => '10']));
        $payload    = json_decode($response->body(), true);

        self::assertSame(200, $response->status());
        self::assertArrayHasKey('pagination', $payload['meta']);
        self::assertSame(10, $payload['meta']['pagination']['limit']);
        self::assertArrayNotHasKey('reference_id', $payload['data'][0]); // never exposed
    }

    public function testTransactionDetailEndpoint(): void
    {
        $txn  = $this->ledgerService->credit(self::USER, 100, 'spin', 'spin', 'r1');
        $uuid = (string) $txn->uuid();

        $request = $this->authed('GET', '/v1/wallet/transactions/' . $uuid);
        $request->setRouteParams(['uuid' => $uuid]);

        $response = (new WalletTransactionController($this->walletService))->show($request);
        $payload  = json_decode($response->body(), true);

        self::assertSame(200, $response->status());
        self::assertSame($uuid, $payload['data']['uuid']);
        self::assertArrayHasKey('metadata', $payload['data']); // detailed view
    }

    public function testTransactionDetailNotFoundForOtherUser(): void
    {
        $txn  = $this->ledgerService->credit(self::USER, 100, 'spin', 'spin', 'r1');
        $uuid = (string) $txn->uuid();

        $request = new Request('GET', '/v1/wallet/transactions/' . $uuid);
        $request->setAttribute('user_id', 999); // different user
        $request->setRouteParams(['uuid' => $uuid]);

        $this->expectException(NotFoundException::class);
        (new WalletTransactionController($this->walletService))->show($request);
    }

    public function testTransactionsListRejectsBadSort(): void
    {
        $controller = new WalletTransactionController($this->walletService);

        $this->expectException(\App\Exceptions\ValidationException::class);
        $controller->index($this->authed('GET', '/v1/wallet/transactions', ['sort' => 'evil']));
    }
}
