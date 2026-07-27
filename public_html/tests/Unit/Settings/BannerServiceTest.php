<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Services\BannerService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryBannerRepository;

final class BannerServiceTest extends TestCase
{
    private InMemoryBannerRepository $banners;

    private BannerService $service;

    protected function setUp(): void
    {
        $this->banners = new InMemoryBannerRepository();
        $this->service = new BannerService($this->banners);
    }

    public function testReturnsBannersOrderedBySortOrder(): void
    {
        $this->banners->seed(['title' => 'Second', 'sort_order' => 2]);
        $this->banners->seed(['title' => 'First', 'sort_order' => 1]);

        $result = $this->service->forPlacement('home_top');

        self::assertCount(2, $result);
        self::assertSame('First', $result[0]['title']);
        self::assertSame('Second', $result[1]['title']);
        self::assertArrayNotHasKey('target_audience', $result[0]);
    }

    public function testFiltersByPlacement(): void
    {
        $this->banners->seed(['placement' => 'home_top']);
        $this->banners->seed(['placement' => 'wallet']);

        self::assertCount(1, $this->service->forPlacement('wallet'));
    }
}
