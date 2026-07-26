<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

/**
 * Validation for POST /v1/auth/logout (API_SPECIFICATION.md §2.8).
 *
 * The refresh token is optional: when supplied, that specific session is revoked;
 * otherwise all of the authenticated user's sessions are revoked.
 */
final class LogoutRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'refresh_token' => 'nullable|string',
        ];
    }
}
