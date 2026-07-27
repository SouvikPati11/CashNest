<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\DeviceTokenRepositoryInterface;
use App\Contracts\FirebaseDispatcherInterface;
use App\Contracts\NotificationPreferenceServiceInterface;
use App\Contracts\NotificationRepositoryInterface;
use App\Models\Notification;
use Core\Contracts\ContainerInterface;
use Core\Contracts\LoggerInterface;

/**
 * Queue job: dispatch a queued notification's push via Firebase.
 *
 * This is the ONLY place FCM is invoked — controllers and services merely
 * enqueue this job. It honors the user's notification preferences and per-device
 * push flags, sends to each active token through the FirebaseDispatcherInterface,
 * and records the resulting `push_status` (sent / failed / skipped).
 *
 * Integration: map this handler in the cron worker registry (bin/cron.php) as
 *   [SendPushNotificationJob::NAME => SendPushNotificationJob::class]
 * — mirrors how feature routes are registered at integration time.
 */
final class SendPushNotificationJob implements JobInterface
{
    /** Queue job name pushed by the notification service. */
    public const NAME = 'notification.push';

    public function handle(array $payload, ContainerInterface $container): void
    {
        $notificationId = (int) ($payload['notification_id'] ?? 0);
        if ($notificationId <= 0) {
            return;
        }

        $notifications = $container->get(NotificationRepositoryInterface::class);
        $logger        = $container->get(LoggerInterface::class);

        $row = $notifications->find($notificationId);
        if ($row === null) {
            $logger->warning('Push job skipped: notification missing.', ['id' => $notificationId]);
            return;
        }

        $notification = Notification::fromRow($row);
        $userId       = $notification->userId();

        $preferences = $container->get(NotificationPreferenceServiceInterface::class);
        if (!$preferences->allowsPush($userId, $notification->type())) {
            $notifications->updatePushStatus($notificationId, Notification::PUSH_SKIPPED);
            return;
        }

        $tokens = $container->get(DeviceTokenRepositoryInterface::class)->activeTokensForUser($userId);
        if ($tokens === []) {
            $notifications->updatePushStatus($notificationId, Notification::PUSH_SKIPPED);
            return;
        }

        $message    = $this->buildMessage($notification, $row);
        $dispatcher = $container->get(FirebaseDispatcherInterface::class);

        $delivered = false;
        foreach ($tokens as $token) {
            if ($dispatcher->send($token, $message)) {
                $delivered = true;
            }
        }

        $notifications->updatePushStatus(
            $notificationId,
            $delivered ? Notification::PUSH_SENT : Notification::PUSH_FAILED
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function buildMessage(Notification $notification, array $row): array
    {
        return [
            'title'     => (string) ($row['title'] ?? ''),
            'body'      => (string) ($row['body'] ?? ''),
            'type'      => $notification->type(),
            'deep_link' => $row['deep_link'] ?? null,
            'image_url' => $row['image_url'] ?? null,
            'data'      => $notification->get('data'),
        ];
    }
}
