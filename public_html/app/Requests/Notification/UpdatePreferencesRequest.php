<?php

declare(strict_types=1);

namespace App\Requests\Notification;

use App\Requests\FormRequest;

/**
 * Validation for updating notification preferences.
 *
 * Every toggle is optional; the client sends any subset and only the supplied
 * toggles are applied.
 */
final class UpdatePreferencesRequest extends FormRequest
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
        ];
    }
}
