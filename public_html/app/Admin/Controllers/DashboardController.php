<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\DashboardService;
use App\Admin\Services\RbacService;
use App\Admin\View\AdminView;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Admin dashboard — headline metrics and a chart.
 */
final class DashboardController extends BaseAdminController
{
    public function __construct(
        AdminView $view,
        AdminAuthService $auth,
        RbacService $rbac,
        private DashboardService $dashboard
    ) {
        parent::__construct($view, $auth, $rbac);
    }

    public function index(Request $request): Response
    {
        $this->authorize('dashboard.view');

        return $this->render('dashboard/index', ['metrics' => $this->dashboard->metrics()], 'dashboard');
    }
}
