<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\RbacService;
use App\Admin\View\AdminView;
use App\Exceptions\ForbiddenException;
use App\Models\Admin;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Base admin controller.
 *
 * Provides HTML/redirect/CSV response helpers, the current-admin identity, and
 * the RBAC gate every admin action calls before mutating or reading privileged
 * data. Controllers stay thin: authorize, delegate to a service, render a view.
 */
abstract class BaseAdminController
{
    public function __construct(
        protected AdminView $view,
        protected AdminAuthService $auth,
        protected RbacService $rbac
    ) {
    }

    /**
     * Enforce that the current admin holds a permission (RBAC gate).
     *
     * @throws ForbiddenException
     */
    protected function authorize(string $permission): void
    {
        $current = $this->auth->current();
        $roleId  = $current !== null ? (int) Admin::fromRow($current)->roleId() : 0;

        if (!$this->rbac->can($roleId, $permission)) {
            throw new ForbiddenException('You do not have permission for this action.', 'FORBIDDEN');
        }
    }

    /**
     * Render a full admin page into an HTML response.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $template, array $data, string $activeKey): Response
    {
        return $this->html($this->view->page($template, $data, $activeKey));
    }

    protected function html(string $body, int $status = 200): Response
    {
        return new Response($status, $body, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    protected function redirect(string $to): Response
    {
        return new Response(302, '', ['Location' => $to]);
    }

    protected function csv(string $body, string $filename): Response
    {
        return new Response(200, $body, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    protected function adminId(): int
    {
        return (int) ($this->auth->id() ?? 0);
    }

    protected function pageParam(Request $request): int
    {
        $page = $request->query('page');

        return is_numeric($page) ? max(1, (int) $page) : 1;
    }

    protected function searchParam(Request $request): ?string
    {
        $search = $request->query('q');

        return is_string($search) && $search !== '' ? $search : null;
    }
}
