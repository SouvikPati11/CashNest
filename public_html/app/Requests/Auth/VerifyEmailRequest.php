<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

/**
 * Validation for POST /v1/auth/email/verify (API_SPECIFICATION.md §2.4).
 *
 * Requires an email plus one of `token` or `otp`; the "at least one" rule is
 * enforced by the controller (the base validator has no cross-field rule for it).
 */
final class VerifyEmailRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'email' => 'required|email',
            'token' => 'nullable|string|max:191',
            'otp'   => 'nullable|digits:6',
        ];
    }
}
