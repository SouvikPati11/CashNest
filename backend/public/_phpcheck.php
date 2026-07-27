<?php

/**
 * CashNest — minimal PHP-handler probe (temporary; delete after use).
 *
 * The smallest possible PHP script. If THIS returns 500 while servercheck.txt
 * returns 200, the PHP handler or this file's permissions are the problem, not
 * your application code. If it prints the line below, PHP executes fine and the
 * 500 is inside the framework (use _diagnostic.php next).
 *
 * No framework, no autoloader, no .env — just PHP.
 */

header('Content-Type: text/plain; charset=utf-8');

echo "PHP handler OK\n";
echo 'Version: ' . PHP_VERSION . "\n";
echo 'SAPI:    ' . PHP_SAPI . "\n";
echo 'Server:  ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo 'Loaded ext: '
    . implode(', ', array_filter(['json', 'pdo', 'pdo_mysql', 'mbstring', 'openssl'], 'extension_loaded'))
    . "\n";
echo "\nDelete this file (public/_phpcheck.php) when finished.\n";
