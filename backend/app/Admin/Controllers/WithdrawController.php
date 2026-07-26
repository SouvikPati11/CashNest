<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\RbacService;
use App\Admin\Services\ReportService;
use App\Admin\Services\WithdrawAdminService;
use App\Admin\View\AdminView;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Withdrawal management — review queue and approve/reject/pay actions.
 *
 * Coin movement is delegated to the existing withdraw settlement service; this
 * controller only authorizes, records the action, and redirects.
 */
final class WithdrawController extends BaseAdminController
{
    public function __construct(
        AdminView $view,
        AdminAuthService $auth,
        RbacService $rbac,
        private WithdrawAdminService $withdrawals,
        private ReportService $reports
    ) {
        parent::__construct($view, $auth, $rbac);
    }

    public function index(Request $request): Response
    {
        $this->authorize('withdraw.view');

        $data = $this->withdrawals->list($this->searchParam($request), $this->pageParam($request), 20);

        return $this->render('withdrawals/index', $data, 'withdrawals');
    }

    public function approve(Request $request): Response
    {
        $this->authorize('withdraw.approve');
        $this->withdrawals->approve($this->adminId(), (int) $request->routeParam('id'), $request->ip());
        $this->view->flash('success', 'Withdrawal approved.');

        return $this->redirect('/admin/withdrawals');
    }

    public function reject(Request $request): Response
    {
        $this->authorize('withdraw.approve');
        $note = $request->input('note');
        $this->withdrawals->reject(
            $this->adminId(),
            (int) $request->routeParam('id'),
            is_string($note) ? $note : null,
            $request->ip()
        );
        $this->view->flash('success', 'Withdrawal rejected and coins released.');

        return $this->redirect('/admin/withdrawals');
    }

    public function pay(Request $request): Response
    {
        $this->authorize('withdraw.approve');
        $reference = $request->input('reference');
        $this->withdrawals->pay(
            $this->adminId(),
            (int) $request->routeParam('id'),
            is_string($reference) ? $reference : null,
            $request->ip()
        );
        $this->view->flash('success', 'Withdrawal marked paid.');

        return $this->redirect('/admin/withdrawals');
    }

    public function export(Request $request): Response
    {
        $this->authorize('withdraw.view');

        return $this->csv($this->reports->exportCsv('withdrawals'), 'withdrawals.csv');
    }
}
