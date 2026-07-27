<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\FirebaseServiceInterface;
use Core\Contracts\LoggerInterface;

/**
 * Default no-op FCM driver.
 *
 * Records intent to the log without contacting Firebase. Keeps the container
 * resolvable at the foundation stage; replace the binding with a real HTTP-v1
 * driver when the Notifications module is built.
 */
final class NullFirebaseService implements FirebaseServiceInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function sendToDevice(string $deviceToken, string $title, string $body, array $data = []): bool
    {
        $this->logger->debug('FCM (null driver) push suppressed.', [
            'title' => $title,
        ]);

        return true;
    }

    public function sendToDevices(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        $count = count($deviceTokens);

        $this->logger->debug('FCM (null driver) multicast suppressed.', [
            'title'   => $title,
            'devices' => $count,
        ]);

        return ['success' => $count, 'failure' => 0];
    }
}
