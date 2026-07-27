<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Contracts\EmailVerificationServiceInterface;
use App\Contracts\UserServiceInterface;
use App\Controllers\BaseController;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use App\Requests\Auth\VerifyEmailRequest;
use App\Resources\UserResource;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Email verification endpoint (API_SPECIFICATION.md §2.4).
 *
 * Accepts a link token or a 6-digit OTP and marks the account verified. Session
 * issuance is out of scope (JWT module), so `tokens` is returned as null.
 */
final class EmailVerificationController extends BaseController
{
    public function __construct(
        private EmailVerificationServiceInterface $verification,
        private UserServiceInterface $users
    ) {
    }

    /**
     * POST /v1/auth/email/verify
     */
    public function verify(Request $request): Response
    {
        $data  = (new VerifyEmailRequest($request))->validated();
        $token = isset($data['token']) && is_string($data['token']) && $data['token'] !== '' ? $data['token'] : null;
        $otp   = isset($data['otp']) && is_string($data['otp']) && $data['otp'] !== '' ? $data['otp'] : null;
        $email = (string) $data['email'];

        if ($token === null && $otp === null) {
            throw new ValidationException(['token' => ['A verification token or OTP is required.']]);
        }

        $userId = $token !== null
            ? $this->verification->verifyToken($token)
            : $this->verification->verifyOtp($email, (string) $otp);

        if ($userId === null) {
            throw new UnauthorizedException(
                'The verification token or OTP is invalid or has expired.',
                'INVALID_TOKEN'
            );
        }

        $this->verification->markVerified($userId);

        $user = $this->users->findById($userId);

        return $this->ok(
            [
                'verified' => true,
                'user'     => $user !== null ? UserResource::toArray($user) : null,
                'tokens'   => null,
            ],
            'Email verified successfully.'
        );
    }
}
