<?php

declare(strict_types=1);

namespace App\Requests\Referral;

use App\Requests\FormRequest;

/**
 * Validation for POST /v1/referral/apply (API_SPECIFICATION.md §2.46).
 */
final class ReferralApplyRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'referral_code' => 'required|string|alpha_num|min:6|max:12',
        ];
    }
}
