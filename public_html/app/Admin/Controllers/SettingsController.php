<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\RbacService;
use App\Admin\View\AdminView;
use App\Contracts\AppSettingRepositoryInterface;
use App\Models\AppSetting;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Admin settings — read-only view of public platform settings.
 */
final class SettingsController extends BaseAdminController
{
    public function __construct(
        AdminView $view,
        AdminAuthService $auth,
        RbacService $rbac,
        private AppSettingRepositoryInterface $appSettings
    ) {
        parent::__construct($view, $auth, $rbac);
    }

    public function index(Request $request): Response
    {
        $this->authorize('settings.view');

        $settings = [];
        foreach ($this->appSettings->publicSettings() as $row) {
            $setting    = AppSetting::fromRow($row);
            $settings[] = ['key' => $setting->key(), 'value' => $setting->typedValue()];
        }

        return $this->render('settings/index', ['settings' => $settings], 'settings');
    }
}
