<?php

declare(strict_types=1);

use Core\Env;

/**
 * JWT signing configuration.
 */
return [
    'secret'      => Env::get('JWT_SECRET', ''),
    'algo'        => Env::get('JWT_ALGO', 'HS256'),
    'issuer'      => Env::get('JWT_ISSUER', 'cashnest'),
    'audience'    => Env::get('JWT_AUDIENCE', 'cashnest-app'),
    'access_ttl'  => (int) Env::get('JWT_ACCESS_TTL', 1800),
    'refresh_ttl' => (int) Env::get('JWT_REFRESH_TTL', 2592000),
];
