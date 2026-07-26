<?php

declare(strict_types=1);

namespace App\Controllers\Withdraw;

use App\Contracts\WithdrawServiceInterface;
use App\Controllers\BaseController;
use App\Exceptions\HttpException;
use App\Requests\Withdraw\WithdrawRequestRequest;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Withdraw endpoints (API_SPECIFICATION.md §2.49–2.53). All JWT-guarded.
 */
final class WithdrawController extends BaseController
{
    public function __construct(private WithdrawServiceInterface $withdraw)
    {
    }

    public function methods(Request $request): Response
    {
        return $this->ok($this->withdraw->methods());
    }

    public function request(Request $request): Response
    {
        $data = (new WithdrawRequestRequest($request))->validated();

        $idempotencyKey = trim((string) ($request->header('x-idempotency-key') ?? ''));
        if ($idempotencyKey === '') {
            throw new HttpException(422, 'VALIDATION_ERROR', 'The X-Idempotency-Key header is required.');
        }

        $detail = is_array($data['payment_detail'] ?? null) ? $data['payment_detail'] : [];

        $result = $this->withdraw->request(
            $this->userId($request),
            (string) $data['method_code'],
            (int) $data['coins_amount'],
            $detail,
            $idempotencyKey,
            $request->ip()
        );

        return $this->created($result, 'Withdrawal requested.');
    }

    public function history(Request $request): Response
    {
        $params = [
            'limit'  => $request->query('limit'),
            'page'   => $request->query('page'),
            'status' => $this->filter($request, 'status'),
        ];

        return $this->paginated($this->withdraw->history($this->userId($request), $params));
    }

    public function show(Request $request): Response
    {
        return $this->ok($this->withdraw->detail($this->userId($request), (string) $request->routeParam('uuid')));
    }

    public function cancel(Request $request): Response
    {
        $result = $this->withdraw->cancel($this->userId($request), (string) $request->routeParam('uuid'));

        return $this->ok($result, 'Withdrawal cancelled.');
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
