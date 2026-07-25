<?php

declare(strict_types=1);

use Core\Env;

/**
 * Cache configuration. File driver is the shared-hosting default.
 */
return [
    'driver' => Env::get('CACHE_DRIVER', 'file'),
    'path'   => Env::get('CACHE_PATH', 'storage/cache'),
];
