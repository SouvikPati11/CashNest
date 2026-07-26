<?php

declare(strict_types=1);

namespace Tests\Unit\Notification;

use App\Exceptions\NotFoundException;
use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use App\Services\Notification\NotificationTemplates;
use App\Services\NotificationService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeQueue;
use Tests\Support\InMemoryNotificationRepository;
use Tests\Support\NullLogger;

final class NotificationServiceTest extends TestCase
{
    private const USER = 1;

    private InMemoryNotificationRepository $repo;

    private FakeQueue $queue;

    private NotificationService $service;

    protected function setUp(): void
    {
        $this->repo    = new InMemoryNotificationRepository();
        $this->queue   = new FakeQueue();
        $this->service = new NotificationService(
            $this->repo,
            $this->queue,
            new NotificationTemplates(),
            new NullLogger()
        );
    }

    public function testSendPersistsRowAndQueuesPush(): void
    {
        $notification = $this->service->send(self::USER, [
            'title' => 'Coins credited',
            'body'  => 'You earned 100 coins.',
            'type'  => Notification::TYPE_TRANSACTIONAL,
        ]);

        self::assertSame(1, $notification->id());
        self::assertSame('queued', $notification->pushStatus());
        self::assertCount(1, $this->repo->rows);

        // The push is queued, not sent inline.
        self::assertCount(1, $this->queue->jobs);
        self::assertSame(SendPushNotificationJob::NAME, $this->queue->jobs[0]['job']);
        self::assertSame(1, $this->queue->jobs[0]['payload']['notification_id']);
    }

    public function testUnknownTypeFallsBackToSystem(): void
    {
        $notification = $this->service->send(self::USER, ['title' => 'x', 'body' => 'y', 'type' => 'bogus']);

        self::assertSame(Notification::TYPE_SYSTEM, $notification->type());
    }

    public function testSendTemplateRendersBodyAndQueues(): void
    {
        $notification = $this->service->sendTemplate(self::USER, 'reward_earned', [
            'coins'  => 250,
            'source' => 'daily check-in',
        ]);

        self::assertSame(Notification::TYPE_TRANSACTIONAL, $notification->type());
        self::assertStringContainsString('250', (string) $notification->get('body'));
        self::assertStringContainsString('daily check-in', (string) $notification->get('body'));
        self::assertCount(1, $this->queue->jobs);
    }

    public function testInboxPaginatesAndShapesResource(): void
    {
        $this->service->send(self::USER, ['title' => 'A', 'body' => 'a']);
        $this->service->send(self::USER, ['title' => 'B', 'body' => 'b']);

        $page = $this->service->inbox(self::USER, ['limit' => 1]);

        self::assertCount(1, $page['items']);
        self::assertTrue($page['has_more']);
        self::assertArrayHasKey('id', $page['items'][0]);
        self::assertArrayNotHasKey('push_status', $page['items'][0]);
    }

    public function testInboxFiltersByReadState(): void
    {
        $first = $this->service->send(self::USER, ['title' => 'A', 'body' => 'a']);
        $this->service->send(self::USER, ['title' => 'B', 'body' => 'b']);
        $this->service->markRead(self::USER, (int) $first->id());

        $unread = $this->service->inbox(self::USER, ['is_read' => 'false']);

        self::assertCount(1, $unread['items']);
        self::assertFalse($unread['items'][0]['is_read']);
    }

    public function testUnreadCount(): void
    {
        $this->service->send(self::USER, ['title' => 'A', 'body' => 'a']);
        $this->service->send(self::USER, ['title' => 'B', 'body' => 'b']);

        self::assertSame(2, $this->service->unreadCount(self::USER));
    }

    public function testMarkReadIsIdempotent(): void
    {
        $n = $this->service->send(self::USER, ['title' => 'A', 'body' => 'a']);

        $first  = $this->service->markRead(self::USER, (int) $n->id());
        $second = $this->service->markRead(self::USER, (int) $n->id());

        self::assertTrue($first['is_read']);
        self::assertTrue($second['is_read']);
        self::assertSame(0, $this->service->unreadCount(self::USER));
    }

    public function testMarkReadNotFoundForOtherUser(): void
    {
        $n = $this->service->send(self::USER, ['title' => 'A', 'body' => 'a']);

        $this->expectException(NotFoundException::class);
        $this->service->markRead(999, (int) $n->id());
    }

    public function testMarkAllRead(): void
    {
        $this->service->send(self::USER, ['title' => 'A', 'body' => 'a']);
        $this->service->send(self::USER, ['title' => 'B', 'body' => 'b']);

        $updated = $this->service->markAllRead(self::USER);

        self::assertSame(2, $updated);
        self::assertSame(0, $this->service->unreadCount(self::USER));
    }
}
