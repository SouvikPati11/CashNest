<?php

/**
 * CashNest REST API — Front Controller.
 *
 * The single public entry point for the application. On shared hosting the web
 * server document root points at this `public/` directory; everything else lives
 * above the web root and is unreachable directly.
 *
 * Hardened for shared hosting (Hostinger / LiteSpeed): any boot-time failure is
 * caught, written to a log file readable via File Manager, and — when
 * APP_DEBUG=true — printed to the browser as HTTP 200 text so it stays visible
 * even when the host replaces 500 error bodies with its own page. Normal
 * requests are unaffected.
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
| public_html-root deployment: the front controller lives at the application
| root, so the base path is this directory. Framework folders (app/, core/,
| config/, vendor/, storage/, .env, ...) sit beside it and are blocked from
| direct HTTP access by the .htaccess in this directory.
*/
$basePath = __DIR__;

/*
|--------------------------------------------------------------------------
| Deployment debug flag
|--------------------------------------------------------------------------
| Read APP_DEBUG directly (the framework may not have booted yet). When true,
| boot failures are shown in the browser as plain text so they are visible on
| hosts that hide PHP fatals behind a generic 500 page.
*/
$cashnestDebug = cashnest_read_debug_flag($basePath . '/.env');

// Make PHP itself surface errors early; the framework refines this once booted.
error_reporting(E_ALL);
ini_set('display_errors', $cashnestDebug ? '1' : '0');
ini_set('log_errors', '1');

/*
|--------------------------------------------------------------------------
| Fatal-error safety net
|--------------------------------------------------------------------------
| try/catch cannot catch parse/compile/OOM fatals; a shutdown handler can.
*/
register_shutdown_function(static function () use ($basePath, $cashnestDebug): void {
    $error = error_get_last();

    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    cashnest_report_boot_failure(
        $basePath,
        $cashnestDebug,
        sprintf('%s in %s on line %d', $error['message'], $error['file'], $error['line']),
        null
    );
});

/*
|--------------------------------------------------------------------------
| Boot & run
|--------------------------------------------------------------------------
| The Composer autoloader, application boot, and request dispatch are wrapped
| so any failure produces a diagnosable response instead of a blank 500.
*/
try {
    $autoload = $basePath . '/vendor/autoload.php';

    if (!is_file($autoload)) {
        throw new RuntimeException(
            'Composer autoloader not found at vendor/autoload.php. '
            . 'Upload the committed vendor/ directory alongside the application.'
        );
    }

    require $autoload;

    $app = Application::boot($basePath);
    $app->run();
} catch (\Throwable $e) {
    cashnest_report_boot_failure($basePath, $cashnestDebug, $e->getMessage(), $e);
}

/*
|--------------------------------------------------------------------------
| Helpers (function declarations are hoisted, so they are usable above)
|--------------------------------------------------------------------------
*/

/**
 * Read APP_DEBUG from the environment or a .env file without booting anything.
 */
function cashnest_read_debug_flag(string $envPath): bool
{
    $truthy = static fn(string $v): bool => in_array(strtolower(trim($v)), ['1', 'true', 'on', 'yes'], true);

    $fromEnv = getenv('APP_DEBUG');
    if ($fromEnv !== false) {
        return $truthy((string) $fromEnv);
    }

    if (is_file($envPath) && is_readable($envPath)) {
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            if (trim($name) === 'APP_DEBUG') {
                return $truthy(trim($value, " \t\"'"));
            }
        }
    }

    return false;
}

/**
 * Log a boot failure and emit a response. In debug mode the details are shown
 * as HTTP 200 text/plain (visible even when the host masks 500 pages); otherwise
 * a generic JSON 500 envelope is returned so clients (and the mobile app) keep
 * receiving the standard error shape.
 */
function cashnest_report_boot_failure(string $basePath, bool $debug, string $message, ?\Throwable $e): void
{
    static $reported = false;
    if ($reported) {
        return; // Avoid double output (try/catch + shutdown handler).
    }
    $reported = true;

    // Persist full details to a file the operator can open in File Manager.
    $logDir = $basePath . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $detail = sprintf(
        "[%s] BOOT FAILURE: %s\n%s\n\n",
        gmdate('Y-m-d\TH:i:s\Z'),
        $message,
        $e !== null ? $e->getTraceAsString() : '(fatal error — no trace available)'
    );
    @file_put_contents($logDir . '/deploy-error.log', $detail, FILE_APPEND | LOCK_EX);

    if (headers_sent()) {
        return;
    }

    if ($debug) {
        // 200 so LiteSpeed/Hostinger does not replace the body with an error page.
        http_response_code(200);
        header('Content-Type: text/plain; charset=utf-8');
        echo "CashNest boot failure (APP_DEBUG is on — set APP_DEBUG=false in production)\n";
        echo str_repeat('=', 72) . "\n\n";
        echo 'Error: ' . $message . "\n\n";
        echo 'PHP version: ' . PHP_VERSION . ' (' . PHP_SAPI . ")\n";
        if ($e !== null) {
            echo 'Type: ' . $e::class . "\n";
            echo 'At:   ' . $e->getFile() . ':' . $e->getLine() . "\n\n";
            echo "Trace:\n" . $e->getTraceAsString() . "\n\n";
        }
        echo "Full details were also written to storage/logs/deploy-error.log\n";
        return;
    }

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'error',
        'message' => 'The service is temporarily unavailable.',
        'data'    => null,
        'meta'    => null,
        'errors'  => [['code' => 'INTERNAL_ERROR', 'field' => null, 'message' => 'Boot failure.']],
    ], JSON_UNESCAPED_SLASHES);
}
