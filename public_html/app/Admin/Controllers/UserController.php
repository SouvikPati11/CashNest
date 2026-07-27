<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\RbacService;
use App\Admin\Services\ReportService;
use App\Admin\Services\UserAdminService;
use App\Admin\View\AdminView;
use Core\Http\Request;
use Core\Http\Response;

/**
 * User management — list/search, detail, status changes, CSV export.
 */
final class UserController extends BaseAdminController
{
    public function __construct(
        AdminView $view,
        AdminAuthService $auth,
        RbacService $rbac,
        private UserAdminService $users,
        private ReportService $reports
    ) {
        parent::__construct($view, $auth, $rbac);
    }

    public function index(Request $request): Response
    {
        $this->authorize('user.view');

        $data = $this->users->list($this->searchParam($request), $this->pageParam($request), 20);

        return $this->render('users/index', $data, 'users');
    }

    public function show(Request $request): Response
    {
        $this->authorize('user.view');

        $user = $this->users->find((int) $request->routeParam('id'));

        return $this->render('users/show', ['user' => $user], 'users');
    }

    public function updateStatus(Request $request): Response
    {
        $this->authorize('user.manage');

        $userId = (int) $request->routeParam('id');
        $status = (string) $request->input('status', '');

        $this->users->setStatus($this->adminId(), $userId, $status, $request->ip());
        $this->view->flash('success', 'User status updated.');

        return $this->redirect('/admin/users/' . $userId);
    }

    public function export(Request $request): Response
    {
        $this->authorize('user.view');

        return $this->csv($this->reports->exportCsv('users'), 'users.csv');
    }
}
