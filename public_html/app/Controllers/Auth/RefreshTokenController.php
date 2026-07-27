<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Contracts\AuthTokenServiceInterface;
use App\Controllers\BaseController;
use App\Requests\Auth\RefreshTokenRequest;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Token refresh endpoint (API_SPECIFICATION.md §2.7).
 *
 * Exchanges a valid refresh token for a new token pair (rotation with reuse
 * detection). Authenticated by the refresh token in the body, not a JWT.
 */
final class RefreshTokenController extends BaseController
{
    public function __construct(private AuthTokenServiceInterface $tokens)
    {
    }

    /**
     * POST /v1/auth/refresh
     */
    public function refresh(Request $request): Response
    {
        $data = (new RefreshTokenRequest($request))->validated();

        $tokens = $this->tokens->refresh((string) $data['refresh_token']);

        return $this->ok(['tokens' => $tokens], 'Token refreshed.');
    }
}
