<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use App\Exceptions\NotFoundException;
use App\Services\AnnouncementService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAnnouncementRepository;

final class AnnouncementServiceTest extends TestCase
{
    private const USER = 1;

    private InMemoryAnnouncementRepository $announcements;

    private AnnouncementService $service;

    protected function setUp(): void
    {
        $this->announcements = new InMemoryAnnouncementRepository();
        $this->service       = new AnnouncementService($this->announcements);
    }

    public function testReturnsActiveOrderedByPriority(): void
    {
        $this->announcements->seed(['title' => 'Low', 'priority' => 200]);
        $this->announcements->seed(['title' => 'High', 'priority' => 10]);

        $result = $this->service->activeFor(self::USER);

        self::assertSame('High', $result[0]['title']);
        self::assertSame('Low', $result[1]['title']);
    }

    public function testMarkSeenExcludesFromFutureList(): void
    {
        $id = $this->announcements->seed(['title' => 'Notice']);

        $result = $this->service->markSeen(self::USER, $id);

        self::assertTrue($result['seen']);
        self::assertCount(0, $this->service->activeFor(self::USER));
    }

    public function testMarkSeenUnknownThrows(): void
    {
        $this->expectException(NotFoundException::class);
        $this->service->markSeen(self::USER, 999);
    }
}
