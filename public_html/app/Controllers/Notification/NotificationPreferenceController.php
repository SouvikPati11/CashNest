<?php

declare(strict_types=1);

namespace App\Controllers\Notification;

use App\Contracts\NotificationPreferenceServiceInterface;
use App\Controllers\BaseController;
use App\Requests\Notification\UpdatePreferencesRequest;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Notification preference endpoints.
 *
 * Manages the notification toggles stored in `user_settings` (the full Settings
 * module is out of scope). The client sends any subset of the toggles.
 */
final class NotificationPreferenceController extends BaseController
{
    public function __construct(private NotificationPreferenceServiceInterface $preferences)
    {
    }

    public function show(Request $request): Response
    {
        return $this->ok($this->preferences->get($this->userId($request)));
    }

    public function update(Request $request): Response
    {
        $data = (new UpdatePreferencesRequest($request))->validated();

        return $this->ok($this->preferences->update($this->userId($request), $data), 'Preferences updated.');
    }

    private function userId(Request $request): int
    {
        $userId = $request->attribute('user_id');

        return is_int($userId) ? $userId : (int) $userId;
    }
}
