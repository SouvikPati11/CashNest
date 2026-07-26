<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Contracts\GoogleLoginServiceInterface;
use App\Controllers\BaseController;
use App\Requests\Auth\GoogleLoginRequest;
use App\Resources\UserResource;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Google login endpoint (API_SPECIFICATION.md §2.1).
 *
 * Verifies a Google ID token, logs in / registers the user, and returns the
 * user plus a fresh JWT access token and rotating refresh token.
 */
final class GoogleLoginController extends BaseController
{
    public function __construct(private GoogleLoginServiceInterface $google)
    {
    }

    /**
     * POST /v1/auth/google
     */
    public function login(Request $request): Response
    {
        $data          = (new GoogleLoginRequest($request))->validated();
        $referralCode  = isset($data['referral_code']) && is_string($data['referral_code'])
            ? $data['referral_code']
            : null;

        $result = $this->google->login(
            (string) $data['id_token'],
            $referralCode,
            $request->ip(),
            $request->userAgent()
        );

        return $this->ok(
            [
                'user'   => UserResource::toArray($result['user'], $result['is_new']),
                'tokens' => $result['tokens'],
            ],
            'Authenticated successfully.'
        );
    }
}
