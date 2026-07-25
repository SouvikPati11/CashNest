<?php

/**
 * CashNest REST API — Front Controller.
 *
 * The single public entry point for the application. On shared hosting the web
 * server document root points at this `public/` directory; everything else lives
 * above the web root and is unreachable directly.
 *
 * @package CashNest
 */

declare(strict_types=1);

use Core\Application;

define('CASHNEST_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Base path
|--------------------------------------------------------------------------
| The application root is one level above this public directory.
*/
$basePath = dirname(__DIR__);

/*
|--------------------------------------------------------------------------
| Composer autoloader
|--------------------------------------------------------------------------
| Fail gracefully with a plain JSON error if dependencies are not installed.
*/
$autoload = $basePath . '/vendor/autoload.php';

if (!is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'Application dependencies are not installed. Run "composer install".',
        'data'    => null,
        'errors'  => [['code' => 'INTERNAL_ERROR', 'field' => null, 'message' => 'Missing autoloader.']],
    ]);
    exit;
}

require $autoload;

/*
|--------------------------------------------------------------------------
| Boot & run the application
|--------------------------------------------------------------------------
| The Application wires configuration, the container, and the router, then
| dispatches the incoming request and emits the response.
*/
$app = Application::boot($basePath);

$app->run();
