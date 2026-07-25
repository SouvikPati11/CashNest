<?php

declare(strict_types=1);

use Core\Env;

/**
 * Application configuration.
 */
return [
    'name'     => Env::get('APP_NAME', 'CashNest'),
    'env'      => Env::get('APP_ENV', 'production'),
    'debug'    => (bool) Env::get('APP_DEBUG', false),
    'url'      => Env::get('APP_URL', 'http://localhost'),
    'timezone' => Env::get('APP_TIMEZONE', 'UTC'),
    'key'      => Env::get('APP_KEY', ''),
];
