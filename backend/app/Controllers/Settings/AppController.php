<?php

declare(strict_types=1);

namespace App\Controllers\Settings;

use App\Contracts\AppVersionServiceInterface;
use App\Contracts\MaintenanceServiceInterface;
use App\Controllers\BaseController;
use Core\Http\Request;
use Core\Http\Response;

/**
 * App version + maintenance endpoints (API_SPECIFICATION.md §2.75–2.76). Public.
 */
final class AppController extends BaseController
{
    public function __construct(
        private AppVersionServiceInterface $version,
        private MaintenanceServiceInterface $maintenance
    ) {
    }

    public function version(Request $request): Response
    {
        $platform    = (string) ($request->header('x-platform') ?? $request->query('platform') ?? 'android');
        $versionCode = $this->intHeader($request, 'x-app-version');

        return $this->ok($this->version->versionInfo($platform, $versionCode));
    }

    public function maintenance(Request $request): Response
    {
        return $this->ok($this->maintenance->status());
    }

    private function intHeader(Request $request, string $name): ?int
    {
        $value = $request->header($name);

        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }
}
