<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Notification;

/**
 * Notification service contract (user-facing inbox + queued dispatch).
 *
 * Creating a notification persists the inbox row and ENQUEUES the push; no FCM
 * call happens inline. Read operations serve the inbox, badge count, and read
 * state.
 */
interface NotificationServiceInterface
{
    /**
     * Create an in-app notification for a user and queue its push.
     *
     * @param array<string, mixed> $attributes title/body/type/deep_link/image_url/data/campaign_id
     */
    public function send(int $userId, array $attributes): Notification;

    /**
     * Create a notification from a named template and queue its push.
     *
     * @param array<string, mixed> $params Template parameters.
     */
    public function sendTemplate(int $userId, string $templateKey, array $params = []): Notification;

    /**
     * A page of the user's inbox.
     *
     * @param array<string, mixed> $params
     * @return array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int}
     */
    public function inbox(int $userId, array $params): array;

    /**
     * Unread badge count for a user.
     */
    public function unreadCount(int $userId): int;

    /**
     * Mark a single notification read (idempotent).
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function markRead(int $userId, int $id): array;

    /**
     * Mark all of a user's notifications read; returns the count updated.
     */
    public function markAllRead(int $userId): int;
}
