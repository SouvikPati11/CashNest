<?php

declare(strict_types=1);

namespace App\Controllers\Wallet;

use App\Contracts\WalletServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Wallet balance endpoints (API_SPECIFICATION.md §2.16, §2.17).
 *
 * Read-only. The authenticated user id is provided by the JWT middleware via the
 * request attribute bag.
 */
final class WalletController extends BaseController
{
    public function __construct(private WalletServiceInterface $wallet)
    {
    }

    /**
     * GET /v1/wallet
     */
    public function balance(Request $request): Response
    {
        return $this->ok($this->wallet->getBalance($this->userId($request)));
    }

    /**
     * GET /v1/wallet/conversion
     */
    public function conversion(Request $request): Response
    {
        return $this->ok($this->wallet->getConversion());
    }

    private function userId(Request $request): int
    {
        $userId = $request->attribute('user_id');

        return is_int($userId) ? $userId : (int) $userId;
    }
}
