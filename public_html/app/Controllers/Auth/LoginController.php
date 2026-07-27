<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Contracts\LoginServiceInterface;
use App\Controllers\BaseController;
use App\Requests\Auth\LoginRequest;
use App\Resources\UserResource;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Email login endpoint (API_SPECIFICATION.md §2.3).
 *
 * Authenticates email/password credentials. Access/refresh token issuance is
 * owned by the JWT module (out of scope here), so `tokens` is returned as null
 * until that module is built; the response shape otherwise matches the spec.
 */
final class LoginController extends BaseController
{
    public function __construct(private LoginServiceInterface $login)
    {
    }

    /**
     * POST /v1/auth/email/login
     */
    public function login(Request $request): Response
    {
        $data = (new LoginRequest($request))->validated();

        $user = $this->login->login((string) $data['email'], (string) $data['password']);

        return $this->ok(
            [
                'user'   => UserResource::toArray($user),
                'tokens' => null,
            ],
            'Login successful.'
        );
    }
}
