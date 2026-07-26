<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AnnouncementRepositoryInterface;
use App\Contracts\AnnouncementServiceInterface;
use App\Exceptions\NotFoundException;
use App\Models\Announcement;
use App\Resources\AnnouncementResource;

/**
 * Announcement service — active announcements and per-user dismissal state.
 *
 * Active, in-window announcements the user has not dismissed are returned,
 * ordered by priority. Marking one seen upserts the dismissal record so it is
 * not shown again (idempotent).
 */
final class AnnouncementService implements AnnouncementServiceInterface
{
    public function __construct(private AnnouncementRepositoryInterface $announcements)
    {
    }

    public function activeFor(int $userId): array
    {
        $now = gmdate('Y-m-d H:i:s');

        return array_map(
            static fn(array $row): array => AnnouncementResource::toArray(Announcement::fromRow($row)),
            $this->announcements->activeForUser($userId, $now)
        );
    }

    public function markSeen(int $userId, int $announcementId): array
    {
        $now = gmdate('Y-m-d H:i:s');

        if ($this->announcements->findActive($announcementId, $now) === null) {
            throw new NotFoundException('Announcement not found.');
        }

        $this->announcements->markSeen($announcementId, $userId, $now);

        return ['seen' => true];
    }
}
