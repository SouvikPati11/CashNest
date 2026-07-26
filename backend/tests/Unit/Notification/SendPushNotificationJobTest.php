<?php

declare(strict_types=1);

namespace Tests\Unit\Notification;

use App\Contracts\DeviceTokenRepositoryInterface;
use App\Contracts\FirebaseDispatcherInterface;
use App\Contracts\NotificationPreferenceServiceInterface;
use App\Contracts\NotificationRepositoryInterface;
use App\Jobs\SendPushNotificationJob;
use App\Models\Notification;
use App\Services\NotificationPreferenceService;
use Core\Container;
use Core\Contracts\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryDeviceTokenRepository;
use Tests\Support\InMemoryNotificationPreferenceRepository;
use Tests\Support\InMemoryNotificationRepository;
use Tests\Support\NullLogger;
use Tests\Support\RecordingFirebaseDispatcher;

final class SendPushNotificationJobTest extends TestCase
{
    private const USER = 1;

    private InMemoryNotificationRepository $notifications;

    private InMemoryDeviceTokenRepository $devices;

    private InMemoryNotificationPreferenceRepository $prefsRepo;

    private Container $container;

    protected function setUp(): void
    {
        $this->notifications = new InMemoryNotificationRepository();
        $this->devices       = new InMemoryDeviceTokenRepository();
        $this->prefsRepo     = new InMemoryNotificationPreferenceRepository();

        $this->container = new Container();
        $this->container->instance(NotificationRepositoryInterface::class, $this->notifications);
        $this->container->instance(LoggerInterface::class, new NullLogger());
        $this->container->instance(
            NotificationPreferenceServiceInterface::class,
            new NotificationPreferenceService($this->prefsRepo)
        );
        $this->container->instance(DeviceTokenRepositoryInterface::class, $this->devices);
    }

    private function seedNotification(string $type = Notification::TYPE_TRANSACTIONAL): int
    {
        return (int) $this->notifications->create([
            'user_id'     => self::USER,
            'title'       => 'Hello',
            'body'        => 'World',
            'type'        => $type,
            'is_read'     => 0,
            'push_status' => Notification::PUSH_QUEUED,
        ]);
    }

    private function dispatchJob(int $id, FirebaseDispatcherInterface $dispatcher): void
    {
        $this->container->instance(FirebaseDispatcherInterface::class, $dispatcher);
        (new SendPushNotificationJob())->handle(['notification_id' => $id], $this->container);
    }

    public function testDeliversAndMarksSent(): void
    {
        $id = $this->seedNotification();
        $this->devices->setTokens(self::USER, ['tok-a', 'tok-b']);
        $dispatcher = new RecordingFirebaseDispatcher(true);

        $this->dispatchJob($id, $dispatcher);

        self::assertSame(Notification::PUSH_SENT, $this->notifications->rows[$id]['push_status']);
        self::assertCount(2, $dispatcher->sent);
    }

    public function testMarksFailedWhenDispatcherRejects(): void
    {
        $id = $this->seedNotification();
        $this->devices->setTokens(self::USER, ['tok-a']);

        $this->dispatchJob($id, new RecordingFirebaseDispatcher(false));

        self::assertSame(Notification::PUSH_FAILED, $this->notifications->rows[$id]['push_status']);
    }

    public function testSkipsWhenNoTokens(): void
    {
        $id = $this->seedNotification();
        $dispatcher = new RecordingFirebaseDispatcher(true);

        $this->dispatchJob($id, $dispatcher);

        self::assertSame(Notification::PUSH_SKIPPED, $this->notifications->rows[$id]['push_status']);
        self::assertCount(0, $dispatcher->sent);
    }

    public function testSkipsWhenPreferencesDisallow(): void
    {
        $id = $this->seedNotification(Notification::TYPE_PROMOTIONAL);
        $this->devices->setTokens(self::USER, ['tok-a']);
        $this->prefsRepo->upsertToggles(self::USER, ['notif_promotional' => 0]);
        $dispatcher = new RecordingFirebaseDispatcher(true);

        $this->dispatchJob($id, $dispatcher);

        self::assertSame(Notification::PUSH_SKIPPED, $this->notifications->rows[$id]['push_status']);
        self::assertCount(0, $dispatcher->sent);
    }

    public function testMissingNotificationIsNoop(): void
    {
        $dispatcher = new RecordingFirebaseDispatcher(true);

        $this->dispatchJob(999, $dispatcher);

        self::assertCount(0, $dispatcher->sent);
        self::assertSame([], $this->notifications->rows);
    }
}
