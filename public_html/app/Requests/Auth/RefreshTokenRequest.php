<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

/**
 * Validation for POST /v1/auth/refresh (API_SPECIFICATION.md §2.7).
 */
final class RefreshTokenRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'refresh_token' => 'required|string',
        ];
    }
}
