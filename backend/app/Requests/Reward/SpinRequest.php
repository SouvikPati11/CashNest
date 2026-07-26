<?php

declare(strict_types=1);

namespace App\Requests\Reward;

use App\Requests\FormRequest;

/**
 * Validation for POST /v1/spin (API_SPECIFICATION.md §2.28).
 */
final class SpinRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'source' => 'nullable|in:free,ad,purchase',
        ];
    }
}
