<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Announcement service contract — active announcements + dismissal state.
 */
interface AnnouncementServiceInterface
{
    /**
     * Active announcements for a user (already-dismissed excluded), ordered by
     * priority.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeFor(int $userId): array;

    /**
     * Mark an announcement seen/dismissed for a user (idempotent).
     *
     * @return array<string, mixed>
     *
     * @throws \App\Exceptions\NotFoundException
     */
    public function markSeen(int $userId, int $announcementId): array;
}
