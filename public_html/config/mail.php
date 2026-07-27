<?php

declare(strict_types=1);

use Core\Env;

/**
 * Mail configuration. Only the "log" driver is wired at the foundation stage.
 */
return [
    'driver' => Env::get('MAIL_DRIVER', 'log'),
    'from'   => [
        'address' => Env::get('MAIL_FROM_ADDRESS', 'no-reply@cashnest.app'),
        'name'    => Env::get('MAIL_FROM_NAME', 'CashNest'),
    ],
];
