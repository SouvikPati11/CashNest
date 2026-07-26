<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\TaskRepositoryInterface;

/**
 * Task repository. Owns `task_completions` and reads the `tasks` catalog.
 */
final class TaskRepository extends BaseRepository implements TaskRepositoryInterface
{
    protected string $table = 'task_completions';

    public function activeTasks(int $limit, int $offset, ?string $type): array
    {
        $now      = gmdate('Y-m-d H:i:s');
        $where    = ['`is_active` = 1'];
        $bindings = [];

        $where[]    = '(`starts_at` IS NULL OR `starts_at` <= ?)';
        $bindings[] = $now;
        $where[]    = '(`ends_at` IS NULL OR `ends_at` >= ?)';
        $bindings[] = $now;

        if ($type !== null && $type !== '') {
            $where[]    = '`task_type` = ?';
            $bindings[] = $type;
        }

        $sql = 'SELECT * FROM `tasks` WHERE ' . implode(' AND ', $where)
            . sprintf(' ORDER BY `sort_order` ASC, `id` ASC LIMIT %d OFFSET %d', $limit, $offset);

        return $this->db->select($sql, $bindings);
    }

    public function find(int|string $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM `tasks` WHERE `id` = ? LIMIT 1', [(int) $id]);
    }

    public function createCompletion(array $data): string
    {
        return $this->create($data);
    }

    public function updateCompletion(int $id, array $data): int
    {
        return $this->update($id, $data);
    }

    public function findCompletionForUser(int $id, int $userId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `task_completions` WHERE `id` = ? AND `user_id` = ? LIMIT 1',
            [$id, $userId]
        );
    }

    public function findStartedForUserTask(int $userId, int $taskId): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM `task_completions`
             WHERE `user_id` = ? AND `task_id` = ? AND `status` = 'started'
             ORDER BY `id` DESC LIMIT 1",
            [$userId, $taskId]
        );
    }

    public function countUserCompletions(int $userId, int $taskId): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS aggregate FROM `task_completions`
             WHERE `user_id` = ? AND `task_id` = ? AND `status` <> 'rejected'",
            [$userId, $taskId]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function countGlobalCompletions(int $taskId): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS aggregate FROM `task_completions`
             WHERE `task_id` = ? AND `status` IN ('approved','credited')",
            [$taskId]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function historyForUser(int $userId, int $limit, int $offset, ?string $status): array
    {
        $where    = ['`user_id` = ?'];
        $bindings = [$userId];

        if ($status !== null && $status !== '') {
            $where[]    = '`status` = ?';
            $bindings[] = $status;
        }

        $sql = 'SELECT * FROM `task_completions` WHERE ' . implode(' AND ', $where)
            . sprintf(' ORDER BY `id` DESC LIMIT %d OFFSET %d', $limit, $offset);

        return $this->db->select($sql, $bindings);
    }
}
