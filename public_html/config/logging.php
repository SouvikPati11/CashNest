<?php

declare(strict_types=1);

use Core\Env;

/**
 * Logging configuration.
 */
return [
    'channel' => Env::get('LOG_CHANNEL', 'file'),
    'level'   => Env::get('LOG_LEVEL', 'info'),
    'path'    => Env::get('LOG_PATH', 'storage/logs'),
];
