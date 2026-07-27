<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Contracts\SettingsServiceInterface;
use App\Controllers\BaseController;
use App\Requests\Settings\UpdateSettingsRequest;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Settings endpoints (API_SPECIFICATION.md §2.58–2.60).
 *
 * `index`/`update` are JWT-guarded (user preferences); `app` is public
 * (version + maintenance summary).
 */
final class SettingsController extends BaseController
{
    public function __construct(private SettingsServiceInterface $settings)
    {
    }

    public function index(Request $request): Response
    {
        return $this->ok($this->settings->getSettings($this->userId($request)));
    }

    public function update(Request $request): Response
    {
        $data = (new UpdateSettingsRequest($request))->validated();

        return $this->ok($this->settings->updateSettings($this->userId($request), $data), 'Settings updated.');
    }

    public function app(Request $request): Response
    {
        $platform    = (string) ($request->header('x-platform') ?? $request->query('platform') ?? 'android');
        $versionCode = $this->intHeader($request, 'x-app-version');

        return $this->ok($this->settings->appStatus($platform, $versionCode));
    }

    private function userId(Request $request): int
    {
        $userId = $request->attribute('user_id');

        return is_int($userId) ? $userId : (int) $userId;
    }

    private function intHeader(Request $request, string $name): ?int
    {
        $value = $request->header($name);

        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }
}
