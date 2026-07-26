<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Services\HomeLayoutService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryHomeSectionRepository;

final class HomeLayoutServiceTest extends TestCase
{
    private InMemoryHomeSectionRepository $sections;

    private HomeLayoutService $service;

    protected function setUp(): void
    {
        $this->sections = new InMemoryHomeSectionRepository();
        $this->service  = new HomeLayoutService($this->sections);
    }

    public function testReturnsOrderedSectionsWithEtag(): void
    {
        $this->sections->seed(['section_type' => 'offers', 'sort_order' => 2, 'config' => ['limit' => 5]]);
        $this->sections->seed(['section_type' => 'banner_carousel', 'sort_order' => 0]);

        [$layout, $etag] = $this->service->layout();

        self::assertSame('banner_carousel', $layout[0]['type']);
        self::assertSame('offers', $layout[1]['type']);
        self::assertSame(['limit' => 5], $layout[1]['config']);
        self::assertStringStartsWith('home_', $etag);
    }

    public function testInactiveSectionsExcluded(): void
    {
        $this->sections->seed(['section_type' => 'offers', 'is_active' => 0]);

        [$layout] = $this->service->layout();

        self::assertCount(0, $layout);
    }
}
