<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AnnouncementRepositoryInterface;
use Core\Database\Database;

/**
 * Announcement repository — owns `announcements` and per-user
 * `announcement_reads` dismissal state.
 */
final class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function activeForUser(int $userId, string $now): array
    {
        return $this->db->select(
            'SELECT a.* FROM `announcements` a'
            . ' LEFT JOIN `announcement_reads` r ON r.`announcement_id` = a.`id` AND r.`user_id` = ?'
            . ' WHERE a.`is_active` = 1 AND r.`id` IS NULL'
            . ' AND (a.`starts_at` IS NULL OR a.`starts_at` <= ?)'
            . ' AND (a.`ends_at` IS NULL OR a.`ends_at` >= ?)'
            . ' ORDER BY a.`priority` ASC, a.`id` DESC',
            [$userId, $now, $now]
        );
    }

    public function findActive(int $id, string $now): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `announcements`'
            . ' WHERE `id` = ? AND `is_active` = 1'
            . ' AND (`starts_at` IS NULL OR `starts_at` <= ?)'
            . ' AND (`ends_at` IS NULL OR `ends_at` >= ?)'
            . ' LIMIT 1',
            [$id, $now, $now]
        );
    }

    public function markSeen(int $announcementId, int $userId, string $seenAt): void
    {
        $this->db->affectingStatement(
            'INSERT INTO `announcement_reads` (`announcement_id`, `user_id`, `seen_at`) VALUES (?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE `seen_at` = VALUES(`seen_at`)',
            [$announcementId, $userId, $seenAt]
        );
    }
}
