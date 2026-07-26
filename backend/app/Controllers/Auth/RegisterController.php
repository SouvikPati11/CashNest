<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Contracts\RegistrationServiceInterface;
use App\Controllers\BaseController;
use App\Requests\Auth\RegisterRequest;
use App\Resources\UserResource;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Email registration endpoint (API_SPECIFICATION.md §2.2).
 *
 * Thin controller: validate input, delegate to the registration service, and
 * shape the standard response. No tokens are issued (verification required).
 */
final class RegisterController extends BaseController
{
    public function __construct(private RegistrationServiceInterface $registration)
    {
    }

    /**
     * POST /v1/auth/email/register
     */
    public function register(Request $request): Response
    {
        $data                    = (new RegisterRequest($request))->validated();
        $data['registration_ip'] = $request->ip();

        $user = $this->registration->register($data);

        return $this->created(
            [
                'user'                  => UserResource::toArray($user, true),
                'verification_required' => true,
            ],
            'Registration successful. Please verify your email.'
        );
    }
}
