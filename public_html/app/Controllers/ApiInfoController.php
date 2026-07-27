<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Config;
use Core\Http\Request;
use Core\Http\Response;

/**
 * API information controller.
 *
 * Serves a small discovery document at the API root and version root so clients
 * can confirm the base URL, current version, and documentation pointer. No
 * business data is exposed.
 */
final class ApiInfoController extends BaseController
{
    public function __construct(private Config $config)
    {
    }

    /**
     * GET / and GET /v1 — API metadata.
     */
    public function index(Request $request): Response
    {
        return $this->ok([
            'name'        => (string) $this->config->get('app.name', 'CashNest'),
            'description' => 'CashNest REST API',
            'api_version' => (string) $this->config->get('api.version', 'v1'),
            'status'      => 'online',
            'docs'        => 'See API_SPECIFICATION.md',
        ], 'Welcome to the CashNest API.');
    }
}
