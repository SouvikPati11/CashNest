<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\RbacService;
use App\Admin\Services\ReportService;
use App\Admin\View\AdminView;
use App\Exceptions\NotFoundException;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Reports — list available exports and stream CSV downloads.
 */
final class ReportController extends BaseAdminController
{
    public function __construct(
        AdminView $view,
        AdminAuthService $auth,
        RbacService $rbac,
        private ReportService $reports
    ) {
        parent::__construct($view, $auth, $rbac);
    }

    public function index(Request $request): Response
    {
        $this->authorize('report.view');

        return $this->render('reports/index', ['reports' => $this->reports->available()], 'reports');
    }

    public function export(Request $request): Response
    {
        $this->authorize('report.view');

        $key = (string) $request->routeParam('key');

        if (!$this->reports->has($key)) {
            throw new NotFoundException('Unknown report.');
        }

        return $this->csv($this->reports->exportCsv($key), $key . '.csv');
    }
}
