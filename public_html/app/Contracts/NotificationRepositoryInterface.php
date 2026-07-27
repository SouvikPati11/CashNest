<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Notification repository contract — owns the `notifications` inbox table.
 */
interface NotificationRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * A row scoped to its owner (authorization).
     *
     * @return array<string, mixed>|null
     */
    public function findForUser(int $id, int $userId): ?array;

    /**
     * A page of the user's inbox, newest first (fetch limit+1 to detect more).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(int $userId, int $limit, int $offset, ?string $type, ?bool $isRead): array;

    /**
     * Count unread notifications for a user (badge count).
     */
    public function unreadCount(int $userId): int;

    /**
     * Mark a single owned row read; returns affected rows.
     */
    public function markRead(int $id, int $userId, string $readAt): int;

    /**
     * Mark all of a user's unread rows read; returns the number updated.
     */
    public function markAllRead(int $userId, string $readAt): int;

    /**
     * Update the FCM delivery state of a notification row.
     */
    public function updatePushStatus(int $id, string $pushStatus): int;
}
