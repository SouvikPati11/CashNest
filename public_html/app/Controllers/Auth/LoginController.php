<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Contracts\AuthTokenServiceInterface;
use App\Contracts\LoginServiceInterface;
use App\Controllers\BaseController;
use App\Requests\Auth\LoginRequest;
use App\Resources\UserResource;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Email login endpoint (API_SPECIFICATION.md §2.3).
 *
 * Authenticates email/password credentials and issues an access/refresh token
 * pair (via AuthTokenService), returning them alongside the user in the standard
 * response shape expected by the client.
 */
final class LoginController extends BaseController
{
    public function __construct(
        private LoginServiceInterface $login,
        private AuthTokenServiceInterface $tokens
    ) {
    }

    /**
     * POST /v1/auth/email/login
     */
    public function login(Request $request): Response
    {
        $data = (new LoginRequest($request))->validated();

        $user = $this->login->login((string) $data['email'], (string) $data['password']);

        $tokens = $this->tokens->issueTokens($user, $request->ip(), $request->userAgent());

        return $this->ok(
            [
                'user'   => UserResource::toArray($user),
                'tokens' => $tokens,
            ],
            'Login successful.'
        );
    }
}
