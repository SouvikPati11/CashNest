<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Config;
use Core\Database\Database;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Health-check controller.
 *
 * Exposes a lightweight liveness/readiness endpoint used by uptime monitors and
 * deploy checks. It reports app metadata and best-effort database connectivity
 * without leaking sensitive configuration.
 */
final class HealthController extends BaseController
{
    public function __construct(
        private Config $config,
        private Database $database
    ) {
    }

    /**
     * GET /v1/health — service status.
     */
    public function index(Request $request): Response
    {
        $database = $this->checkDatabase();

        $healthy = $database === 'connected' || $database === 'skipped';

        return $this->ok(
            [
                'service'     => (string) $this->config->get('app.name', 'CashNest'),
                'status'      => $healthy ? 'healthy' : 'degraded',
                'environment' => (string) $this->config->get('app.env', 'production'),
                'api_version' => (string) $this->config->get('api.version', 'v1'),
                'checks'      => [
                    'database' => $database,
                ],
                'time'        => gmdate('Y-m-d\TH:i:s\Z'),
            ],
            $healthy ? 'Service is healthy.' : 'Service is degraded.',
            $healthy ? 200 : 503
        );
    }

    /**
     * Attempt a trivial query to confirm DB connectivity.
     */
    private function checkDatabase(): string
    {
        // Skip when DB credentials are not configured (e.g. first boot).
        if ((string) $this->config->get('database.connections.mysql.database', '') === '') {
            return 'skipped';
        }

        try {
            $this->database->selectOne('SELECT 1 AS ok');
            return 'connected';
        } catch (\Throwable) {
            return 'unavailable';
        }
    }
}
