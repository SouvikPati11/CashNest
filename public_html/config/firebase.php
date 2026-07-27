<?php

declare(strict_types=1);

use Core\Env;

/**
 * Firebase / FCM configuration. Only the null driver is wired at the
 * foundation stage; the credentials path must point OUTSIDE the web root.
 */
return [
    'project_id'       => Env::get('FIREBASE_PROJECT_ID', ''),
    'credentials_path' => Env::get('FIREBASE_CREDENTIALS_PATH', ''),
];
