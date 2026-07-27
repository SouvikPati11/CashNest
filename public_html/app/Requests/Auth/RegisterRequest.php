<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Requests\FormRequest;

/**
 * Validation for POST /v1/auth/email/register (API_SPECIFICATION.md §2.2).
 */
final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'name'          => 'required|string|min:2|max:120',
            'email'         => 'required|email|max:255',
            'password'      => 'required|string|min:8|max:72|regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
            'referral_code' => 'nullable|string|alpha_num|min:6|max:12',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'password.regex' => 'The password must contain at least one letter and one number.',
        ];
    }
}
