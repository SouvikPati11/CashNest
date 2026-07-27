<?php

declare(strict_types=1);

namespace Tests\Unit\Offerwall;

use App\Contracts\OfferwallPostbackServiceInterface as Postback;
use App\Contracts\ReferralCommissionServiceInterface;
use App\Models\WalletTransaction;
use App\Services\LedgerService;
use App\Services\OfferwallPostbackService;
use App\Services\PostbackSignatureVerifier;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeTransactionRunner;
use Tests\Support\InMemoryOfferwallProviderRepository;
use Tests\Support\InMemoryPostbackRepository;
use Tests\Support\InMemoryWalletRepository;
use Tests\Support\InMemoryWalletTransactionRepository;
use Tests\Support\NullLogger;

final class OfferwallPostbackServiceTest extends TestCase
{
    private const USER   = 7;
    private const SECRET = 'provider-secret';
    private const SLUG   = 'adgate';

    private InMemoryOfferwallProviderRepository $providers;

    private InMemoryPostbackRepository $postbacks;

    private InMemoryWalletRepository $wallets;

    private InMemoryWalletTransactionRepository $ledgerRepo;

    /** @var ReferralCommissionServiceInterface&object{calls:int} */
    private object $referral;

    private OfferwallPostbackService $service;

    protected function setUp(): void
    {
        $this->providers  = new InMemoryOfferwallProviderRepository();
        $this->postbacks  = new InMemoryPostbackRepository();
        $this->wallets    = new InMemoryWalletRepository();
        $this->ledgerRepo = new InMemoryWalletTransactionRepository();

        $this->providers->seed([
            'slug' => self::SLUG, 'name' => 'AdGate', 'postback_secret_enc' => self::SECRET,
            'currency_ratio' => 1.0, 'ip_allowlist' => null,
        ]);
        $this->postbacks->seedClick(['user_id' => self::USER, 'offer_id' => 1, 'provider_id' => 1, 'click_token' => 'tok-1']);

        $ledger = new LedgerService(new FakeTransactionRunner(), $this->wallets, $this->ledgerRepo, new NullLogger());

        $this->referral = new class implements ReferralCommissionServiceInterface {
            public int $calls = 0;

            public function applyForEarning(int $earnerUserId, WalletTransaction $earning): void
            {
                $this->calls++;
            }
        };

        $this->service = new OfferwallPostbackService(
            $this->providers,
            $this->postbacks,
            new PostbackSignatureVerifier(),
            $ledger,
            $this->referral,
            new NullLogger()
        );
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        $p = array_merge(['transaction_id' => 'TX-1', 'sub_id' => 'tok-1', 'payout' => '100'], $overrides);
        $p['signature'] = hash_hmac('sha256', $p['transaction_id'] . '|' . $p['sub_id'] . '|' . $p['payout'], self::SECRET);

        return $p;
    }

    public function testValidPostbackCreditsThroughLedger(): void
    {
        $result = $this->service->process('offerwall', self::SLUG, $this->payload(), '1.2.3.4', 'POST');

        self::assertSame(Postback::RESULT_CREDITED, $result['result']);
        self::assertSame(200, $result['http_status']);
        self::assertCount(1, $this->ledgerRepo->rows);
        self::assertSame('offerwall', $this->ledgerRepo->rows[1]['type']);
        self::assertSame(100, (int) $this->wallets->findByUserId(self::USER)['coin_balance']);
        self::assertSame('credited', $this->postbacks->conversions[1]['status']);
        self::assertSame(1, $this->referral->calls); // commission fired
    }

    public function testDuplicateTransactionIsNotRecredited(): void
    {
        $this->service->process('offerwall', self::SLUG, $this->payload(), '1.2.3.4', 'POST');
        $second = $this->service->process('offerwall', self::SLUG, $this->payload(), '1.2.3.4', 'POST');

        self::assertSame(Postback::RESULT_DUPLICATE, $second['result']);
        self::assertSame(200, $second['http_status']);
        self::assertCount(1, $this->ledgerRepo->rows);          // still one credit
        self::assertSame(100, (int) $this->wallets->findByUserId(self::USER)['coin_balance']);
    }

    public function testInvalidSignatureRejected(): void
    {
        $payload = $this->payload();
        $payload['signature'] = 'tampered';

        $result = $this->service->process('offerwall', self::SLUG, $payload, '1.2.3.4', 'POST');

        self::assertSame(Postback::RESULT_INVALID_SIG, $result['result']);
        self::assertSame(401, $result['http_status']);
        self::assertCount(0, $this->ledgerRepo->rows);
        // Still audited.
        self::assertNotEmpty($this->postbacks->logs);
    }

    public function testIpBlockedWhenNotOnAllowlist(): void
    {
        $this->providers->providers[1]['ip_allowlist'] = ['10.0.0.1', '192.168.1.0/24'];

        $result = $this->service->process('offerwall', self::SLUG, $this->payload(), '8.8.8.8', 'POST');

        self::assertSame(Postback::RESULT_IP_BLOCKED, $result['result']);
        self::assertSame(403, $result['http_status']);
        self::assertCount(0, $this->ledgerRepo->rows);
    }

    public function testAllowlistCidrMatchPasses(): void
    {
        $this->providers->providers[1]['ip_allowlist'] = ['192.168.1.0/24'];

        $result = $this->service->process('offerwall', self::SLUG, $this->payload(), '192.168.1.55', 'POST');

        self::assertSame(Postback::RESULT_CREDITED, $result['result']);
    }

    public function testUnknownClickTokenReturnsUserNotFound(): void
    {
        $result = $this->service->process('offerwall', self::SLUG, $this->payload(['sub_id' => 'nope']), '1.2.3.4', 'POST');

        self::assertSame(Postback::RESULT_USER_NOT_FOUND, $result['result']);
        self::assertSame(404, $result['http_status']);
        self::assertCount(0, $this->ledgerRepo->rows);
    }

    public function testUnknownProviderReturnsError(): void
    {
        $result = $this->service->process('offerwall', 'ghost', $this->payload(), '1.2.3.4', 'POST');

        self::assertSame(Postback::RESULT_ERROR, $result['result']);
        self::assertSame(404, $result['http_status']);
    }

    public function testCpaKindUsesCpaLedgerType(): void
    {
        $result = $this->service->process('cpa', self::SLUG, $this->payload(['transaction_id' => 'CPA-1']), '1.2.3.4', 'GET');

        self::assertSame(Postback::RESULT_CREDITED, $result['result']);
        self::assertSame('cpa', $this->ledgerRepo->rows[1]['type']);
    }
}
