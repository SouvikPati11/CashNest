<?php

declare(strict_types=1);

namespace App\Controllers\Wallet;

use App\Contracts\WalletServiceInterface;
use App\Controllers\BaseController;
use App\Exceptions\NotFoundException;
use App\Requests\Wallet\TransactionHistoryRequest;
use App\Resources\TransactionResource;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Wallet transaction (ledger) endpoints (API_SPECIFICATION.md §2.18, §2.19).
 *
 * Read-only and strictly user-scoped.
 */
final class WalletTransactionController extends BaseController
{
    public function __construct(private WalletServiceInterface $wallet)
    {
    }

    /**
     * GET /v1/wallet/transactions
     */
    public function index(Request $request): Response
    {
        $params = (new TransactionHistoryRequest($request))->validated();

        $result = $this->wallet->transactionHistory($this->userId($request), $params);

        $pagination = [
            'limit'       => $result['limit'],
            'next_cursor' => $result['next_cursor'],
            'prev_cursor' => null,
            'has_more'    => $result['has_more'],
        ];

        if (!$result['use_cursor']) {
            $pagination['page'] = $result['page'];
        }

        return $this->ok(
            TransactionResource::collection($result['items']),
            'OK',
            200,
            ['pagination' => $pagination]
        );
    }

    /**
     * GET /v1/wallet/transactions/{uuid}
     */
    public function show(Request $request): Response
    {
        $uuid = (string) $request->routeParam('uuid');

        $transaction = $this->wallet->findTransactionForUser($uuid, $this->userId($request));

        if ($transaction === null) {
            throw new NotFoundException('Transaction not found.');
        }

        return $this->ok(TransactionResource::toArray($transaction, true));
    }

    private function userId(Request $request): int
    {
        $userId = $request->attribute('user_id');

        return is_int($userId) ? $userId : (int) $userId;
    }
}
