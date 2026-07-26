<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\FraudAdminService;
use App\Admin\Services\RbacService;
use App\Admin\View\AdminView;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Fraud management — review queue and flag resolution.
 */
final class FraudController extends BaseAdminController
{
    public function __construct(
        AdminView $view,
        AdminAuthService $auth,
        RbacService $rbac,
        private FraudAdminService $fraud
    ) {
        parent::__construct($view, $auth, $rbac);
    }

    public function index(Request $request): Response
    {
        $this->authorize('fraud.view');

        $status = $request->query('status');
        $data   = $this->fraud->list(
            is_string($status) ? $status : null,
            $this->pageParam($request),
            20
        );

        return $this->render('fraud/index', $data, 'fraud');
    }

    public function resolve(Request $request): Response
    {
        $this->authorize('fraud.manage');

        $this->fraud->resolve(
            $this->adminId(),
            (int) $request->routeParam('id'),
            (string) $request->input('status', ''),
            (string) $request->input('action', 'none'),
            $request->ip()
        );
        $this->view->flash('success', 'Fraud flag updated.');

        return $this->redirect('/admin/fraud');
    }
}
