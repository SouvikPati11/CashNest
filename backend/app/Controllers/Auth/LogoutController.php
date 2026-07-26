<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Contracts\AuthTokenServiceInterface;
use App\Controllers\BaseController;
use App\Requests\Auth\LogoutRequest;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Logout endpoint (API_SPECIFICATION.md §2.8).
 *
 * Requires a valid access token (JWT middleware). Revokes the session for the
 * supplied refresh token, or all of the user's sessions when none is given.
 */
final class LogoutController extends BaseController
{
    public function __construct(private AuthTokenServiceInterface $tokens)
    {
    }

    /**
     * POST /v1/auth/logout
     */
    public function logout(Request $request): Response
    {
        $data  = (new LogoutRequest($request))->validated();
        $token = $data['refresh_token'] ?? null;
        $refreshToken = is_string($token) && $token !== '' ? $token : null;

        if ($refreshToken !== null) {
            $this->tokens->logout($refreshToken);
        } else {
            $userId = $request->attribute('user_id');
            if (is_int($userId)) {
                $this->tokens->logoutAll($userId);
            }
        }

        return $this->ok(['logged_out' => true], 'Logged out successfully.');
    }
}
