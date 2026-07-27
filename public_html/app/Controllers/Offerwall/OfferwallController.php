<?php

declare(strict_types=1);

namespace App\Controllers\Offerwall;

use App\Contracts\OfferwallServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Offerwall endpoints (API_SPECIFICATION.md §2.35–2.38).
 */
final class OfferwallController extends BaseController
{
    public function __construct(private OfferwallServiceInterface $offerwall)
    {
    }

    public function providers(Request $request): Response
    {
        return $this->ok($this->offerwall->providers());
    }

    public function offers(Request $request): Response
    {
        $params = [
            'limit'    => $request->query('limit'),
            'page'     => $request->query('page'),
            'sort'     => $request->query('sort'),
            'provider' => $this->filter($request, 'provider'),
            'category' => $this->filter($request, 'category'),
        ];

        return $this->paginated($this->offerwall->offers($params));
    }

    public function show(Request $request): Response
    {
        return $this->ok($this->offerwall->offerDetail((int) $request->routeParam('id')));
    }

    public function click(Request $request): Response
    {
        $result = $this->offerwall->recordClick(
            $this->userId($request),
            (int) $request->routeParam('id'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->ok($result, 'Click recorded.');
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

        return is_string($value) ? $value : null;
    }
}
