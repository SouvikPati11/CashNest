<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\LedgerServiceInterface;
use App\Contracts\TaskRepositoryInterface;
use App\Contracts\TaskServiceInterface;
use App\Exceptions\HttpException;
use App\Exceptions\NotFoundException;
use App\Models\Task;
use App\Models\TaskCompletion;
use Core\Contracts\LoggerInterface;

/**
 * Task service.
 *
 * List/detail, start (attribution), and complete. Auto-verified tasks credit
 * ONLY through the LedgerService, keyed `task:<completionId>` so a retry never
 * double-credits; manual/callback tasks move to `pending` for later review.
 * Respects per-user and global completion caps.
 */
final class TaskService implements TaskServiceInterface
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT     = 100;

    public function __construct(
        private TaskRepositoryInterface $tasks,
        private LedgerServiceInterface $ledger,
        private LoggerInterface $logger
    ) {
    }

    public function listTasks(int $userId, array $params): array
    {
        $limit = $this->resolveLimit($params['limit'] ?? null);
        $page  = max(1, (int) ($params['page'] ?? 1));
        $type  = is_string($params['task_type'] ?? null) ? $params['task_type'] : null;

        $rows = $this->tasks->activeTasks($limit + 1, ($page - 1) * $limit, $type);

        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        $items = array_map(
            fn(array $row): array => $this->present(Task::fromRow($row), $userId),
            $rows
        );

        return ['items' => $items, 'has_more' => $hasMore, 'page' => $page, 'limit' => $limit];
    }

    public function detail(int $userId, int $taskId): array
    {
        $task = $this->requireAvailableTask($taskId);

        return $this->present($task, $userId);
    }

    public function start(int $userId, int $taskId): array
    {
        $task = $this->requireAvailableTask($taskId);

        $this->assertWithinCaps($task, $userId);

        // Reuse an in-progress attempt instead of stacking duplicates.
        $existing = $this->tasks->findStartedForUserTask($userId, $taskId);
        if ($existing !== null) {
            return ['completion_id' => (int) $existing['id'], 'status' => TaskCompletion::STATUS_STARTED];
        }

        $completionId = (int) $this->tasks->createCompletion([
            'user_id'      => $userId,
            'task_id'      => $taskId,
            'status'       => TaskCompletion::STATUS_STARTED,
            'reward_coins' => $task->rewardCoins(),
        ]);

        return ['completion_id' => $completionId, 'status' => TaskCompletion::STATUS_STARTED];
    }

    public function complete(int $userId, int $taskId, array $data): array
    {
        $taskRow = $this->tasks->find($taskId);
        if ($taskRow === null) {
            throw new NotFoundException('Task not found.');
        }
        $task = Task::fromRow($taskRow);

        $startedRow = $this->tasks->findStartedForUserTask($userId, $taskId);
        if ($startedRow === null) {
            throw new HttpException(409, 'RESOURCE_CONFLICT', 'Start the task before completing it.');
        }

        $completion   = TaskCompletion::fromRow($startedRow);
        $completionId = (int) $completion->id();

        $verificationRef = isset($data['verification_ref']) && is_string($data['verification_ref'])
            ? $data['verification_ref']
            : null;
        $proofUrl = isset($data['proof_url']) && is_string($data['proof_url']) ? $data['proof_url'] : null;

        if ($task->verificationType() === Task::VERIFY_AUTO) {
            return $this->creditCompletion($userId, $completionId, $task->rewardCoins(), $verificationRef, $proofUrl);
        }

        // Manual / callback: queue for review, no credit yet.
        $this->tasks->updateCompletion($completionId, [
            'status'           => TaskCompletion::STATUS_PENDING,
            'verification_ref' => $verificationRef,
            'proof_url'        => $proofUrl,
            'completed_at'     => gmdate('Y-m-d H:i:s'),
        ]);

        return ['status' => TaskCompletion::STATUS_PENDING, 'coins_awarded' => 0];
    }

    public function history(int $userId, array $params): array
    {
        $limit  = $this->resolveLimit($params['limit'] ?? null);
        $page   = max(1, (int) ($params['page'] ?? 1));
        $status = is_string($params['status'] ?? null) ? $params['status'] : null;

        $rows = $this->tasks->historyForUser($userId, $limit + 1, ($page - 1) * $limit, $status);

        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $limit);
        }

        $items = array_map(static fn(array $r): array => [
            'completion_id' => (int) $r['id'],
            'task_id'       => (int) $r['task_id'],
            'status'        => (string) $r['status'],
            'reward_coins'  => (int) $r['reward_coins'],
            'created_at'    => $r['created_at'] ?? null,
        ], $rows);

        return ['items' => $items, 'has_more' => $hasMore, 'page' => $page, 'limit' => $limit];
    }

    /**
     * Credit an auto-verified completion through the ledger (idempotent).
     *
     * @return array<string, mixed>
     */
    private function creditCompletion(
        int $userId,
        int $completionId,
        int $rewardCoins,
        ?string $verificationRef,
        ?string $proofUrl
    ): array {
        $transactionUuid = null;
        $newBalance      = null;
        $transactionId   = null;

        if ($rewardCoins > 0) {
            $txn = $this->ledger->credit(
                $userId,
                $rewardCoins,
                'task',
                'task',
                "task:{$completionId}",
                ['source_id' => $completionId]
            );
            $transactionUuid = $txn->uuid();
            $newBalance      = (int) $txn->get('balance_after', 0);
            $transactionId   = $txn->id();
        }

        $this->tasks->updateCompletion($completionId, [
            'status'           => TaskCompletion::STATUS_CREDITED,
            'reward_coins'     => $rewardCoins,
            'verification_ref' => $verificationRef,
            'proof_url'        => $proofUrl,
            'transaction_id'   => $transactionId,
            'completed_at'     => gmdate('Y-m-d H:i:s'),
        ]);

        $this->logger->info('Task auto-credited.', ['user_id' => $userId, 'completion_id' => $completionId]);

        return [
            'status'           => TaskCompletion::STATUS_CREDITED,
            'coins_awarded'    => $rewardCoins,
            'new_balance'      => $newBalance,
            'transaction_uuid' => $transactionUuid,
        ];
    }

    private function requireAvailableTask(int $taskId): Task
    {
        $row = $this->tasks->find($taskId);

        if ($row === null) {
            throw new NotFoundException('Task not found.');
        }

        $task = Task::fromRow($row);

        if (!$task->isAvailable(time())) {
            throw new HttpException(409, 'RESOURCE_CONFLICT', 'This task is not currently available.');
        }

        return $task;
    }

    private function assertWithinCaps(Task $task, int $userId): void
    {
        $taskId = (int) $task->id();

        if ($this->tasks->countUserCompletions($userId, $taskId) >= $task->perUserLimit()) {
            throw new HttpException(429, 'LIMIT_REACHED', 'You have reached the limit for this task.');
        }

        $max = $task->get('max_completions');
        if ($max !== null && $this->tasks->countGlobalCompletions($taskId) >= (int) $max) {
            throw new HttpException(409, 'RESOURCE_CONFLICT', 'This task is no longer available.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Task $task, int $userId): array
    {
        $taskId = (int) $task->id();

        return [
            'id'              => $taskId,
            'title'           => $task->get('title'),
            'description'     => $task->get('description'),
            'task_type'       => $task->get('task_type'),
            'reward_coins'    => $task->rewardCoins(),
            'action_url'      => $task->get('action_url'),
            'icon_url'        => $task->get('icon_url'),
            'per_user_limit'  => $task->perUserLimit(),
            'my_completions'  => $this->tasks->countUserCompletions($userId, $taskId),
        ];
    }

    private function resolveLimit(mixed $limit): int
    {
        $value = is_numeric($limit) ? (int) $limit : self::DEFAULT_LIMIT;

        return max(1, min($value, self::MAX_LIMIT));
    }
}
