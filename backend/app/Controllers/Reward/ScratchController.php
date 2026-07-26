<?php

declare(strict_types=1);

namespace App\Controllers\Reward;

use App\Contracts\ScratchServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Scratch card endpoints (API_SPECIFICATION.md §2.23–2.26).
 */
final class ScratchController extends BaseController
{
    public function __construct(private ScratchServiceInterface $scratch)
    {
    }

    public function available(Request $request): Response
    {
        return $this->ok($this->scratch->available($this->userId($request)));
    }

    public function reveal(Request $request): Response
    {
        $result = $this->scratch->reveal($this->userId($request), $this->cardId($request));

        return $this->ok($result, 'Card revealed.');
    }

    public function claim(Request $request): Response
    {
        $result = $this->scratch->claim($this->userId($request), $this->cardId($request));

        return $this->ok($result, 'Reward claimed.');
    }

    public function history(Request $request): Response
    {
        $params = [
            'limit'  => $request->query('limit'),
            'page'   => $request->query('page'),
            'status' => $this->filter($request, 'status'),
        ];

        $result = $this->scratch->history($this->userId($request), $params);

        $pagination = [
            'limit'    => $result['limit'],
            'page'     => $result['page'],
            'has_more' => $result['has_more'],
        ];

        return $this->ok($result['items'], 'OK', 200, ['pagination' => $pagination]);
    }

    private function cardId(Request $request): int
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
