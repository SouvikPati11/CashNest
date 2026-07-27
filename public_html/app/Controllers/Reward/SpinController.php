<?php

declare(strict_types=1);

namespace App\Controllers\Reward;

use App\Contracts\SpinServiceInterface;
use App\Controllers\BaseController;
use App\Requests\Reward\SpinRequest;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Spin wheel endpoints (API_SPECIFICATION.md §2.27–2.29).
 */
final class SpinController extends BaseController
{
    public function __construct(private SpinServiceInterface $spin)
    {
    }

    public function status(Request $request): Response
    {
        return $this->ok($this->spin->status($this->userId($request)));
    }

    public function spin(Request $request): Response
    {
        $data   = (new SpinRequest($request))->validated();
        $source = isset($data['source']) && is_string($data['source']) ? $data['source'] : 'free';

        return $this->ok($this->spin->spin($this->userId($request), $source), 'Spin complete.');
    }

    public function history(Request $request): Response
    {
        $params = ['limit' => $request->query('limit'), 'page' => $request->query('page')];

        $result = $this->spin->history($this->userId($request), $params);

        $pagination = [
            'limit'    => $result['limit'],
            'page'     => $result['page'],
            'has_more' => $result['has_more'],
        ];

        return $this->ok($result['items'], 'OK', 200, ['pagination' => $pagination]);
    }

    private function userId(Request $request): int
    {
        $userId = $request->attribute('user_id');

        return is_int($userId) ? $userId : (int) $userId;
    }
}
