<?php

declare(strict_types=1);

namespace App\Admin\Controllers;

use Core\Http\Request;
use Core\Http\Response;

/**
 * Backup & restore — operational status page.
 *
 * Database backup/restore is executed by scheduled infrastructure jobs (out of
 * the request path); this page surfaces the policy and status to admins.
 */
final class BackupController extends BaseAdminController
{
    public function index(Request $request): Response
    {
        $this->authorize('backup.view');

        return $this->render('backup/index', [], 'backup');
    }
}
