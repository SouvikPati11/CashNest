<?php

declare(strict_types=1);

namespace Tests\Unit\Offerwall;

use App\Exceptions\NotFoundException;
use App\Services\OfferwallService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryOfferRepository;
use Tests\Support\InMemoryOfferwallProviderRepository;
use Tests\Support\InMemoryPostbackRepository;

final class OfferwallServiceTest extends TestCase
{
    private InMemoryOfferwallProviderRepository $providers;

    private InMemoryOfferRepository $offers;

    private InMemoryPostbackRepository $postbacks;

    private OfferwallService $service;

    protected function setUp(): void
    {
        $this->providers = new InMemoryOfferwallProviderRepository();
        $this->offers    = new InMemoryOfferRepository();
        $this->postbacks = new InMemoryPostbackRepository();
        $this->service   = new OfferwallService($this->providers, $this->offers, $this->postbacks);
    }

    public function testProvidersListsActive(): void
    {
        $this->providers->seed(['slug' => 'adgate', 'name' => 'AdGate', 'logo_url' => 'https://l']);

        $list = $this->service->providers();

        self::assertCount(1, $list);
        self::assertSame('adgate', $list[0]['slug']);
        // Secrets never surface.
        self::assertArrayNotHasKey('postback_secret_enc', $list[0]);
    }

    public function testOffersListPaginates(): void
    {
        $this->offers->seedOffer(['title' => 'A', 'payout_coins' => 100]);
        $this->offers->seedOffer(['title' => 'B', 'payout_coins' => 200]);

        $result = $this->service->offers(['limit' => 1]);

        self::assertCount(1, $result['items']);
        self::assertTrue($result['has_more']);
    }

    public function testOfferDetailNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->offerDetail(999);
    }

    public function testRecordClickCreatesTokenAndRedirect(): void
    {
        $offerId = $this->offers->seedOffer([
            'provider_id'  => 1,
            'title'        => 'Install app',
            'tracking_url' => 'https://provider.test/click?offer=5',
        ]);

        $result = $this->service->recordClick(42, $offerId, '1.2.3.4', 'agent');

        self::assertNotEmpty($result['click_token']);
        self::assertStringContainsString('s=' . $result['click_token'], $result['redirect_url']);
        self::assertCount(1, $this->postbacks->clicks);
        self::assertSame(42, $this->postbacks->clicks[1]['user_id']);
    }

    public function testRecordClickSubstitutesSubIdPlaceholder(): void
    {
        $offerId = $this->offers->seedOffer(['tracking_url' => 'https://p.test/go?s={sub_id}']);

        $result = $this->service->recordClick(1, $offerId, null, null);

        self::assertStringContainsString('s=' . $result['click_token'], $result['redirect_url']);
        self::assertStringNotContainsString('{sub_id}', $result['redirect_url']);
    }
}
