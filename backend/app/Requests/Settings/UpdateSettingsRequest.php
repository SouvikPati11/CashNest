<?php

declare(strict_types=1);

namespace App\Requests\Settings;

use App\Requests\FormRequest;

/**
 * Validation for PUT /v1/settings (API_SPECIFICATION.md §2.59).
 *
 * Every field is optional; only supplied preferences are applied. Enum/length
 * whitelists guard theme mode and language.
 */
final class UpdateSettingsRequest extends FormRequest
{
    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'notif_push_enabled'  => 'boolean',
            'notif_transactional' => 'boolean',
            'notif_promotional'   => 'boolean',
            'language'            => 'string|max:10',
            'theme_mode'          => 'in:system,light,dark',
        ];
    }
}
