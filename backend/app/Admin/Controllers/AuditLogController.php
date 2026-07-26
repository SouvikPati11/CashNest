<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\AuditLogService;
use App\Admin\Services\RbacService;
use App\Admin\View\AdminView;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Audit log browser — filterable, paginated view of admin actions.
 */
final class AuditLogController extends BaseAdminController
{
    public function __construct(
        AdminView $view,
        AdminAuthService $auth,
        RbacService $rbac,
        private AuditLogService $audit
    ) {
        parent::__construct($view, $auth, $rbac);
    }

    public function index(Request $request): Response
    {
        $this->authorize('audit.view');

        $action = $request->query('action');
        $data   = $this->audit->recent(
            is_string($action) && $action !== '' ? $action : null,
            null,
            $this->pageParam($request),
            20
        );

        return $this->render('audit/index', $data, 'audit_logs');
    }
}
