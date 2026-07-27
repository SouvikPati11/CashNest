<?php

declare(strict_types=1);

use Core\Env;

/**
 * Queue configuration. File driver is the shared-hosting default.
 */
return [
    'driver' => Env::get('QUEUE_DRIVER', 'file'),
    'path'   => Env::get('QUEUE_PATH', 'storage/queue'),
];
