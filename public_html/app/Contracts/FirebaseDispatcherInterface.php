<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Firebase Cloud Messaging dispatcher contract (interface only).
 *
 * The concrete FCM HTTP v1 implementation (credentials, transport, token
 * pruning on UNREGISTERED) is intentionally NOT built in this module. The
 * interface lets the push-dispatch job depend on an abstraction so a real
 * dispatcher can be dropped in later without touching the queue pipeline.
 *
 * Implementations MUST be side-effect isolated and never throw for a single
 * bad token — they return a per-token boolean so the caller can record
 * delivery stats and prune stale tokens.
 */
interface FirebaseDispatcherInterface
{
    /**
     * Deliver a message to a single device token.
     *
     * @param array<string, mixed> $message Title/body/data/deep_link payload.
     * @return bool True when the provider accepted the message.
     */
    public function send(string $token, array $message): bool;
}
