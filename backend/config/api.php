<?php

declare(strict_types=1);

use Core\Env;

/**
 * API configuration (versioning + default throttling).
 */
return [
    'version'     => Env::get('API_VERSION', 'v1'),
    'rate_limit'  => (int) Env::get('API_RATE_LIMIT', 120),
    'rate_window' => (int) Env::get('API_RATE_WINDOW', 60),
];
