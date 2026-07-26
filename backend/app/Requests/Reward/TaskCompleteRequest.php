<?php

declare(strict_types=1);

namespace App\Requests\Reward;

use App\Requests\FormRequest;

/**
 * Validation for POST /v1/tasks/{id}/complete (API_SPECIFICATION.md §2.33).
 */
final class TaskCompleteRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'verification_ref' => 'nullable|string|max:191',
        ];
    }
}
