<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

/**
 * Validation for POST /v1/auth/google (API_SPECIFICATION.md §2.1).
 */
final class GoogleLoginRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'id_token'      => 'required|string',
            'referral_code' => 'nullable|string|alpha_num|min:6|max:12',
        ];
    }
}
