<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use App\Admin\Services\AdminAuthService;
use App\Admin\Services\RbacService;
use App\Admin\Services\ResourceAdminService;
use App\Admin\View\AdminView;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Generic content/config management browser.
 *
 * Serves the list view for any registered resource (banners, announcements,
 * themes, home layout, CMS, FAQ, remote config, gateways, ad networks, rewards,
 * offerwall, referrals, notifications, wallets) via one metadata-driven flow.
 */
final class ResourceController extends BaseAdminController
{
    public function __construct(
        AdminView $view,
        AdminAuthService $auth,
        RbacService $rbac,
        private ResourceAdminService $resources
    ) {
        parent::__construct($view, $auth, $rbac);
    }

    public function index(Request $request): Response
    {
        $key  = (string) $request->routeParam('resource');
        $data = $this->resources->list($key, $this->searchParam($request), $this->pageParam($request), 20);

        $this->authorize((string) $data['permission']);

        return $this->render('resource/index', $data, $key);
    }
}
