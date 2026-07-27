<?php

/**
 * CashNest — Deployment self-check (temporary tool).
 *
 * A framework-free diagnostic that reports the most common shared-hosting
 * problems as a plain-text HTTP 200 page, so it is visible even when the host
 * masks PHP fatals behind a generic 500 error page. Upload alongside index.php,
 * open it in the browser, read the results, then DELETE it.
 *
 * Safety: this page only runs when APP_DEBUG=true in .env; otherwise it refuses
 * to disclose anything. Delete it once the deployment is healthy.
 *
 * URL: https://your-domain/<path-to>/public/_diagnostic.php
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
http_response_code(200);

$basePath = dirname(__DIR__);

/** Minimal .env reader (no framework dependency). */
$env = static function (string $key, ?string $default = null) use ($basePath): ?string {
    $osv = getenv($key);
    if ($osv !== false) {
        return $osv;
    }
    $path = $basePath . '/.env';
    if (is_file($path) && is_readable($path)) {
        foreach (@file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            if (trim($name) === $key) {
                return trim($value, " \t\"'");
            }
        }
    }
    return $default;
};

$truthy = static fn(?string $v): bool => in_array(strtolower((string) $v), ['1', 'true', 'on', 'yes'], true);

if (!$truthy($env('APP_DEBUG'))) {
    echo "Diagnostics are disabled.\n";
    echo "Temporarily set APP_DEBUG=true in .env, reload this page, then set it back to false and delete this file.\n";
    exit;
}

$pass = static fn(bool $ok): string => $ok ? '[ PASS ]' : '[ FAIL ]';
$line = str_repeat('=', 72);

echo "CashNest deployment self-check\n$line\n\n";

// --- PHP runtime -----------------------------------------------------------
echo "PHP\n";
$phpOk = PHP_VERSION_ID >= 80100;
printf("  %s PHP version: %s (SAPI: %s)  [need >= 8.1]\n", $pass($phpOk), PHP_VERSION, PHP_SAPI);

// --- Required extensions ---------------------------------------------------
echo "\nExtensions\n";
foreach (['json', 'pdo', 'pdo_mysql', 'mbstring', 'openssl'] as $ext) {
    printf("  %s ext-%s\n", $pass(extension_loaded($ext)), $ext);
}

// --- Composer autoloader ---------------------------------------------------
echo "\nComposer autoloader\n";
$autoload = $basePath . '/vendor/autoload.php';
$autoloadOk = is_file($autoload);
printf("  %s vendor/autoload.php present\n", $pass($autoloadOk));
if ($autoloadOk) {
    require $autoload;
    printf("  %s App\\ classes load (App\\Models\\User)\n", $pass(class_exists(\App\Models\User::class)));
    printf("  %s Core\\ classes load (Core\\Application)\n", $pass(class_exists(\Core\Application::class)));
    printf("  %s firebase/php-jwt present (Firebase\\JWT\\JWT)\n", $pass(class_exists(\Firebase\JWT\JWT::class)));
}

// --- .env & key config -----------------------------------------------------
echo "\nEnvironment (.env)\n";
printf("  %s .env file present and readable\n", $pass(is_file($basePath . '/.env') && is_readable($basePath . '/.env')));
foreach (['APP_ENV', 'APP_URL', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'] as $k) {
    $v = $env($k);
    printf("  %s %-14s = %s\n", $pass($v !== null && $v !== ''), $k, $v === null ? '(missing)' : $v);
}
printf("  %s JWT_SECRET set (not the placeholder)\n", $pass(($s = (string) $env('JWT_SECRET')) !== '' && !str_contains($s, 'change')));

// --- Storage writability ---------------------------------------------------
echo "\nStorage (must be writable by PHP)\n";
foreach (['storage/logs', 'storage/cache', 'storage/queue', 'storage/uploads'] as $rel) {
    $dir = $basePath . '/' . $rel;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $ok = is_dir($dir) && is_writable($dir);
    printf("  %s %s%s\n", $pass($ok), $rel, is_dir($dir) ? '' : ' (missing)');
}

// --- Database connectivity -------------------------------------------------
echo "\nDatabase (PDO connect using .env)\n";
try {
    $host = (string) $env('DB_HOST', '127.0.0.1');
    $port = (string) $env('DB_PORT', '3306');
    $name = (string) $env('DB_DATABASE', '');
    $dsn  = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    $pdo  = new PDO($dsn, (string) $env('DB_USERNAME', ''), (string) $env('DB_PASSWORD', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $tables = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();
    printf("  %s Connected to '%s' (%d tables)\n", $pass(true), $name, $tables);
} catch (\Throwable $e) {
    printf("  %s Connection failed: %s\n", $pass(false), $e->getMessage());
}

// --- Recent boot errors ----------------------------------------------------
echo "\nRecent boot errors (storage/logs/deploy-error.log)\n";
$errLog = $basePath . '/storage/logs/deploy-error.log';
if (is_file($errLog)) {
    $tail = array_slice(explode("\n", (string) file_get_contents($errLog)), -25);
    echo "  " . str_replace("\n", "\n  ", trim(implode("\n", $tail))) . "\n";
} else {
    echo "  (none recorded)\n";
}

echo "\n$line\nDone. Set APP_DEBUG=false and DELETE this file (public/_diagnostic.php) when finished.\n";
