<?php

declare(strict_types=1);

use Core\Env;

/**
 * CORS configuration. Comma-separated env values are split into arrays.
 */
$split = static function (string $value): array {
    return array_values(array_filter(array_map('trim', explode(',', $value))));
};

return [
    'allowed_origins' => $split((string) Env::get('CORS_ALLOWED_ORIGINS', '*')),
    'allowed_methods' => $split((string) Env::get('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),
    'allowed_headers' => $split((string) Env::get(
        'CORS_ALLOWED_HEADERS',
        'Authorization,Content-Type,Accept,X-App-Version,X-Platform,X-Device-Id,X-Idempotency-Key,X-Request-Id'
    )),
    'max_age'         => (int) Env::get('CORS_MAX_AGE', 86400),
];
