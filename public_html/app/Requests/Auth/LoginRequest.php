<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

/**
 * Validation for POST /v1/auth/email/login (API_SPECIFICATION.md §2.3).
 */
final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'email'    => 'required|email',
            'password' => 'required|string',
        ];
    }
}
