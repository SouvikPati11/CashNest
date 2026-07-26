<?php

declare(strict_types=1);

namespace App\Controllers\Reward;

use App\Contracts\TaskServiceInterface;
use App\Controllers\BaseController;
use App\Models\TaskCompletion;
use App\Requests\Reward\TaskCompleteRequest;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Task endpoints (API_SPECIFICATION.md §2.30–2.34).
 */
final class TaskController extends BaseController
{
    public function __construct(private TaskServiceInterface $tasks)
    {
    }

    public function index(Request $request): Response
    {
        $params = [
            'limit'     => $request->query('limit'),
            'page'      => $request->query('page'),
            'task_type' => $this->filter($request, 'task_type'),
        ];

        $result = $this->tasks->listTasks($this->userId($request), $params);

        return $this->paginated($result);
    }

    public function show(Request $request): Response
    {
        return $this->ok($this->tasks->detail($this->userId($request), $this->taskId($request)));
    }

    public function start(Request $request): Response
    {
        return $this->ok($this->tasks->start($this->userId($request), $this->taskId($request)), 'Task started.');
    }

    public function complete(Request $request): Response
    {
        $data   = (new TaskCompleteRequest($request))->validated();
        $result = $this->tasks->complete($this->userId($request), $this->taskId($request), $data);

        // Manual/callback tasks are queued (202); auto-verified are credited (200).
        if (($result['status'] ?? null) === TaskCompletion::STATUS_PENDING) {
            return $this->accepted($result, 'Task submitted for review.');
        }

        return $this->ok($result, 'Task completed.');
    }

    public function history(Request $request): Response
    {
        $params = [
            'limit'  => $request->query('limit'),
            'page'   => $request->query('page'),
            'status' => $this->filter($request, 'status'),
        ];

        return $this->paginated($this->tasks->history($this->userId($request), $params));
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int} $result
     */
    private function paginated(array $result): Response
    {
        $pagination = [
            'limit'    => $result['limit'],
            'page'     => $result['page'],
            'has_more' => $result['has_more'],
        ];

        return $this->ok($result['items'], 'OK', 200, ['pagination' => $pagination]);
    }

    private function taskId(Request $request): int
    {
        return (int) $request->routeParam('id');
    }

    private function userId(Request $request): int
    {
        $userId = $request->attribute('user_id');

        return is_int($userId) ? $userId : (int) $userId;
    }

    private function filter(Request $request, string $key): ?string
    {
        $filter = $request->query('filter');
        $value  = is_array($filter) ? ($filter[$key] ?? null) : null;

        return is_string($value) ? $value : null;
    }
}
