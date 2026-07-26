<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\TaskRepositoryInterface;

final class InMemoryTaskRepository implements TaskRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $tasks = [];

    /** @var array<int, array<string, mixed>> */
    public array $completions = [];

    private int $nextCompletionId = 1;

    public function seedTask(array $data): int
    {
        $id = (int) ($data['id'] ?? (count($this->tasks) + 1));
        $this->tasks[$id] = array_merge([
            'id'                => $id,
            'is_active'         => 1,
            'per_user_limit'    => 1,
            'max_completions'   => null,
            'verification_type' => 'auto',
            'reward_coins'      => 0,
            'sort_order'        => 0,
            'starts_at'         => null,
            'ends_at'           => null,
        ], $data, ['id' => $id]);

        return $id;
    }

    public function activeTasks(int $limit, int $offset, ?string $type): array
    {
        $rows = array_values(array_filter($this->tasks, static function (array $t) use ($type): bool {
            return (int) $t['is_active'] === 1 && ($type === null || $type === '' || ($t['task_type'] ?? null) === $type);
        }));

        return array_slice($rows, $offset, $limit);
    }

    public function find(int|string $id): ?array
    {
        return $this->tasks[(int) $id] ?? null;
    }

    public function createCompletion(array $data): string
    {
        $id = $this->nextCompletionId++;
        $this->completions[$id] = $data + ['id' => $id];

        return (string) $id;
    }

    public function updateCompletion(int $id, array $data): int
    {
        if (!isset($this->completions[$id])) {
            return 0;
        }
        $this->completions[$id] = array_merge($this->completions[$id], $data);

        return 1;
    }

    public function findCompletionForUser(int $id, int $userId): ?array
    {
        $c = $this->completions[$id] ?? null;

        return $c !== null && $c['user_id'] === $userId ? $c : null;
    }

    public function findStartedForUserTask(int $userId, int $taskId): ?array
    {
        foreach (array_reverse($this->completions, true) as $c) {
            if ($c['user_id'] === $userId && $c['task_id'] === $taskId && $c['status'] === 'started') {
                return $c;
            }
        }

        return null;
    }

    public function countUserCompletions(int $userId, int $taskId): int
    {
        $count = 0;
        foreach ($this->completions as $c) {
            if ($c['user_id'] === $userId && $c['task_id'] === $taskId && $c['status'] !== 'rejected') {
                $count++;
            }
        }

        return $count;
    }

    public function countGlobalCompletions(int $taskId): int
    {
        $count = 0;
        foreach ($this->completions as $c) {
            if ($c['task_id'] === $taskId && in_array($c['status'], ['approved', 'credited'], true)) {
                $count++;
            }
        }

        return $count;
    }

    public function historyForUser(int $userId, int $limit, int $offset, ?string $status): array
    {
        $rows = array_values(array_filter($this->completions, static function (array $c) use ($userId, $status): bool {
            return $c['user_id'] === $userId && ($status === null || $status === '' || $c['status'] === $status);
        }));

        usort($rows, static fn(array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return array_slice($rows, $offset, $limit);
    }
}
