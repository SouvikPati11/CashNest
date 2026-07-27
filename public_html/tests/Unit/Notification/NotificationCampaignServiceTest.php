<?php

declare(strict_types=1);

namespace Tests\Unit\Notification;

use App\Exceptions\HttpException;
use App\Models\NotificationCampaign;
use App\Services\Notification\NotificationTemplates;
use App\Services\NotificationCampaignService;
use App\Services\NotificationService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeQueue;
use Tests\Support\InMemoryNotificationCampaignRepository;
use Tests\Support\InMemoryNotificationRepository;
use Tests\Support\NullLogger;

final class NotificationCampaignServiceTest extends TestCase
{
    private InMemoryNotificationCampaignRepository $campaigns;

    private InMemoryNotificationRepository $notifications;

    private FakeQueue $queue;

    private NotificationCampaignService $service;

    protected function setUp(): void
    {
        $this->campaigns     = new InMemoryNotificationCampaignRepository();
        $this->notifications = new InMemoryNotificationRepository();
        $this->queue         = new FakeQueue();

        $notificationService = new NotificationService(
            $this->notifications,
            $this->queue,
            new NotificationTemplates(),
            new NullLogger()
        );

        $this->service = new NotificationCampaignService(
            $this->campaigns,
            $notificationService,
            new NullLogger()
        );
    }

    public function testCreateStartsInDraft(): void
    {
        $campaign = $this->service->create([
            'title' => 'Weekend bonus',
            'body'  => 'Double coins all weekend!',
            'type'  => NotificationCampaign::TYPE_PROMOTIONAL,
        ]);

        self::assertSame('draft', $campaign->status());
        self::assertNotNull($campaign->id());
    }

    public function testDispatchFansOutAndQueuesEachPush(): void
    {
        $campaign = $this->service->create([
            'title' => 'Weekend bonus',
            'body'  => 'Double coins all weekend!',
            'type'  => NotificationCampaign::TYPE_PROMOTIONAL,
        ]);

        $result = $this->service->dispatch((int) $campaign->id(), [10, 20, 30]);

        self::assertSame('sent', $result->status());
        self::assertSame(3, (int) $this->campaigns->rows[(int) $campaign->id()]['total_targeted']);
        self::assertSame(3, (int) $this->campaigns->rows[(int) $campaign->id()]['total_sent']);
        self::assertSame(0, (int) $this->campaigns->rows[(int) $campaign->id()]['total_failed']);

        // One inbox row + one queued push per recipient.
        self::assertCount(3, $this->notifications->rows);
        self::assertCount(3, $this->queue->jobs);
        self::assertSame((int) $campaign->id(), (int) $this->notifications->rows[1]['campaign_id']);
        self::assertSame('promotional', $this->notifications->rows[1]['type']);
    }

    public function testDispatchDeduplicatesRecipients(): void
    {
        $campaign = $this->service->create(['title' => 'T', 'body' => 'B']);

        $this->service->dispatch((int) $campaign->id(), [5, 5, 6]);

        self::assertCount(2, $this->notifications->rows);
    }

    public function testDispatchTwiceConflicts(): void
    {
        $campaign = $this->service->create(['title' => 'T', 'body' => 'B']);
        $this->service->dispatch((int) $campaign->id(), [1]);

        try {
            $this->service->dispatch((int) $campaign->id(), [2]);
            self::fail('Expected RESOURCE_CONFLICT.');
        } catch (HttpException $e) {
            self::assertSame(409, $e->getStatusCode());
            self::assertSame('RESOURCE_CONFLICT', $e->getErrorCode());
        }
    }
}
