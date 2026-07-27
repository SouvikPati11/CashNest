<?php

declare(strict_types=1);

namespace App\Controllers\Notification;

use App\Contracts\NotificationServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Notification inbox endpoints (API_SPECIFICATION.md §2.54–2.57).
 *
 * Rows are addressed by numeric id (the finalised schema has no uuid column).
 * No FCM call happens here — creation enqueues; these endpoints only read/mark.
 */
final class NotificationController extends BaseController
{
    public function __construct(private NotificationServiceInterface $notifications)
    {
    }

    public function index(Request $request): Response
    {
        $params = [
            'limit'   => $request->query('limit'),
            'page'    => $request->query('page'),
            'type'    => $this->filter($request, 'type'),
            'is_read' => $this->filter($request, 'is_read'),
        ];

        return $this->paginated($this->notifications->inbox($this->userId($request), $params));
    }

    public function unreadCount(Request $request): Response
    {
        return $this->ok(['unread' => $this->notifications->unreadCount($this->userId($request))]);
    }

    public function read(Request $request): Response
    {
        $result = $this->notifications->markRead($this->userId($request), (int) $request->routeParam('id'));

        return $this->ok($result);
    }

    public function readAll(Request $request): Response
    {
        $updated = $this->notifications->markAllRead($this->userId($request));

        return $this->ok(['updated' => $updated]);
    }

    /**
     * @param array{items: array<int, array<string, mixed>>, has_more: bool, page: int, limit: int} $result
     */
    private function paginated(array $result): Response
    {
        $pagination = ['limit' => $result['limit'], 'page' => $result['page'], 'has_more' => $result['has_more']];

        return $this->ok($result['items'], 'OK', 200, ['pagination' => $pagination]);
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

        return is_scalar($value) ? (string) $value : null;
    }
}
