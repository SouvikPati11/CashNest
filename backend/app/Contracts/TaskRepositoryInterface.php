<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Task repository contract (tasks + task_completions).
 */
interface TaskRepositoryInterface
{
    /**
     * Active, in-window tasks (ordered by sort_order).
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeTasks(int $limit, int $offset, ?string $type): array;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array;

    /**
     * @param array<string, mixed> $data
     */
    public function createCompletion(array $data): string;

    /**
     * @param array<string, mixed> $data
     */
    public function updateCompletion(int $id, array $data): int;

    /**
     * @return array<string, mixed>|null
     */
    public function findCompletionForUser(int $id, int $userId): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findStartedForUserTask(int $userId, int $taskId): ?array;

    /**
     * Count a user's non-rejected completions of a task (per-user-limit check).
     */
    public function countUserCompletions(int $userId, int $taskId): int;

    /**
     * Count all credited/approved completions of a task (global-cap check).
     */
    public function countGlobalCompletions(int $taskId): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function historyForUser(int $userId, int $limit, int $offset, ?string $status): array;
}
