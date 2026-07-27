<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Announcement repository contract — owns `announcements` and the per-user
 * `announcement_reads` dismissal state.
 */
interface AnnouncementRepositoryInterface
{
    /**
     * Active, in-window announcements the user has not yet dismissed, ordered by
     * priority. Pass userId 0 for anonymous callers (no dismissal filtering).
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeForUser(int $userId, string $now): array;

    /**
     * An active, in-window announcement by id (for the seen action).
     *
     * @return array<string, mixed>|null
     */
    public function findActive(int $id, string $now): ?array;

    /**
     * Upsert a per-user dismissal record (idempotent).
     */
    public function markSeen(int $announcementId, int $userId, string $seenAt): void;
}
