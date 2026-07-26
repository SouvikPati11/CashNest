<?php

declare(strict_types=1);

namespace App\Controllers\Offerwall;

use App\Contracts\OfferwallServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * CPA offer endpoints (API_SPECIFICATION.md §2.40–2.41).
 */
final class CpaController extends BaseController
{
    public function __construct(private OfferwallServiceInterface $offerwall)
    {
    }

    public function offers(Request $request): Response
    {
        $params = [
            'limit'    => $request->query('limit'),
            'page'     => $request->query('page'),
            'provider' => $this->filter($request, 'provider'),
        ];

        $result     = $this->offerwall->cpaOffers($params);
        $pagination = ['limit' => $result['limit'], 'page' => $result['page'], 'has_more' => $result['has_more']];

        return $this->ok($result['items'], 'OK', 200, ['pagination' => $pagination]);
    }

    public function show(Request $request): Response
    {
        return $this->ok($this->offerwall->cpaOfferDetail((int) $request->routeParam('id')));
    }

    private function filter(Request $request, string $key): ?string
    {
        $filter = $request->query('filter');
        $value  = is_array($filter) ? ($filter[$key] ?? null) : null;

        return is_string($value) ? $value : null;
    }
}
