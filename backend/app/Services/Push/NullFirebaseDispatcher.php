<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Contracts\FirebaseDispatcherInterface;
use Core\Contracts\LoggerInterface;

/**
 * Placeholder Firebase dispatcher.
 *
 * The concrete FCM HTTP v1 client is intentionally out of scope for this module
 * (the deliverable is the dispatcher INTERFACE). This no-op stand-in keeps the
 * queue pipeline wired and testable: it logs the intended delivery and reports
 * that nothing was sent, so notifications are recorded as `failed` until a real
 * dispatcher is bound in its place. Swap this binding for the production FCM
 * dispatcher without touching the job or services.
 */
final class NullFirebaseDispatcher implements FirebaseDispatcherInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function send(string $token, array $message): bool
    {
        $this->logger->warning('FCM dispatcher not configured; push not delivered.', [
            'token_suffix' => substr($token, -6),
            'title'        => $message['title'] ?? null,
        ]);

        return false;
    }
}
