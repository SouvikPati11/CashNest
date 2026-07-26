<?php

declare(strict_types=1);

namespace App\Controllers\Referral;

use App\Contracts\ReferralServiceInterface;
use App\Controllers\BaseController;
use App\Requests\Referral\ReferralApplyRequest;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Referral endpoints (API_SPECIFICATION.md §2.43–2.46).
 */
final class ReferralController extends BaseController
{
    public function __construct(private ReferralServiceInterface $referrals)
    {
    }

    public function index(Request $request): Response
    {
        return $this->ok($this->referrals->overview($this->userId($request)));
    }

    public function list(Request $request): Response
    {
        $params = [
            'limit'  => $request->query('limit'),
            'page'   => $request->query('page'),
            'status' => $this->filter($request, 'status'),
        ];

        return $this->paginated($this->referrals->listReferrals($this->userId($request), $params));
    }

    public function earnings(Request $request): Response
    {
        $params = ['limit' => $request->query('limit'), 'page' => $request->query('page')];

        return $this->paginated($this->referrals->earnings($this->userId($request), $params));
    }

    public function apply(Request $request): Response
    {
        $data = (new ReferralApplyRequest($request))->validated();

        $result = $this->referrals->apply($this->userId($request), (string) $data['referral_code'], $request->ip());

        return $this->ok($result, 'Referral code applied.');
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
