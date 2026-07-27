<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Services\ResourceAdminService;
use App\Admin\Support\AdminResources;
use App\Exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAdminQueryRepository;

final class ResourceAdminServiceTest extends TestCase
{
    private InMemoryAdminQueryRepository $query;

    private ResourceAdminService $service;

    protected function setUp(): void
    {
        $this->query = new InMemoryAdminQueryRepository();
        $this->query->seed('banners', [
            ['id' => 1, 'title' => 'Spin promo', 'placement' => 'home_top'],
            ['id' => 2, 'title' => 'Refer promo', 'placement' => 'wallet'],
        ]);

        $this->service = new ResourceAdminService(new AdminResources(), $this->query);
    }

    public function testListsResourceRowsAndMeta(): void
    {
        $result = $this->service->list('banners', null, 1, 20);

        self::assertSame('Banners', $result['resource']['title']);
        self::assertCount(2, $result['rows']);
        self::assertSame(2, $result['paginator']->total);
    }

    public function testSearchesResource(): void
    {
        $result = $this->service->list('banners', 'spin', 1, 20);

        self::assertCount(1, $result['rows']);
        self::assertSame('Spin promo', $result['rows'][0]['title']);
    }

    public function testUnknownResourceThrows(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->list('does_not_exist', null, 1, 20);
    }
}
