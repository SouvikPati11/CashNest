<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Firebase Cloud Messaging contract.
 *
 * Abstracts push delivery so controllers/services depend on the interface, not
 * the HTTP-v1 details. Only the interface and a no-op driver exist at the
 * foundation stage; the real FCM driver is added with the Notifications module.
 */
interface FirebaseServiceInterface
{
    /**
     * Send a push notification to a single device token.
     *
     * @param string               $deviceToken FCM registration token.
     * @param string               $title       Notification title.
     * @param string               $body        Notification body.
     * @param array<string, mixed> $data        Optional data payload (deep links, ids).
     * @return bool True when accepted by FCM.
     */
    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): bool;

    /**
     * Send a push notification to many device tokens.
     *
     * @param array<int, string>   $deviceTokens FCM tokens.
     * @param string               $title        Notification title.
     * @param string               $body         Notification body.
     * @param array<string, mixed> $data         Optional data payload.
     * @return array{success: int, failure: int} Delivery summary.
     */
    public function sendToDevices(array $deviceTokens, string $title, string $body, array $data = []): array;
}
