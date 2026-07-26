<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\NotificationRepositoryInterface;

/**
 * Notification repository — owns the `notifications` inbox table.
 *
 * The inbox list and badge count are served by the composite index
 * `(user_id, is_read, created_at)`.
 */
final class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    protected string $table = 'notifications';

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `notifications` WHERE `id` = ? AND `user_id` = ? LIMIT 1',
            [$id, $userId]
        );
    }

    public function listForUser(int $userId, int $limit, int $offset, ?string $type, ?bool $isRead): array
    {
        $where    = ['`user_id` = ?'];
        $bindings = [$userId];

        if ($type !== null && $type !== '') {
            $where[]    = '`type` = ?';
            $bindings[] = $type;
        }

        if ($isRead !== null) {
            $where[]    = '`is_read` = ?';
            $bindings[] = $isRead ? 1 : 0;
        }

        $sql = 'SELECT * FROM `notifications` WHERE ' . implode(' AND ', $where)
            . sprintf(' ORDER BY `id` DESC LIMIT %d OFFSET %d', $limit, $offset);

        return $this->db->select($sql, $bindings);
    }

    public function unreadCount(int $userId): int
    {
        $row = $this->db->selectOne(
            'SELECT COUNT(*) AS aggregate FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0',
            [$userId]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function markRead(int $id, int $userId, string $readAt): int
    {
        return $this->db->affectingStatement(
            'UPDATE `notifications` SET `is_read` = 1, `read_at` = ?'
            . ' WHERE `id` = ? AND `user_id` = ? AND `is_read` = 0',
            [$readAt, $id, $userId]
        );
    }

    public function markAllRead(int $userId, string $readAt): int
    {
        return $this->db->affectingStatement(
            'UPDATE `notifications` SET `is_read` = 1, `read_at` = ? WHERE `user_id` = ? AND `is_read` = 0',
            [$readAt, $userId]
        );
    }

    public function updatePushStatus(int $id, string $pushStatus): int
    {
        return $this->db->affectingStatement(
            'UPDATE `notifications` SET `push_status` = ? WHERE `id` = ?',
            [$pushStatus, $id]
        );
    }
}
