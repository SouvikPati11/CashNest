<?php

declare(strict_types=1);

use Core\Env;

/**
 * Google Sign-In configuration.
 *
 * `client_id` must match the OAuth client used by the app; the Google ID token's
 * `aud` claim is verified against it. Set GOOGLE_CLIENT_ID in the environment
 * (add it to your .env — not committed).
 */
return [
    'client_id'     => Env::get('GOOGLE_CLIENT_ID', ''),
    'tokeninfo_url' => Env::get('GOOGLE_TOKENINFO_URL', 'https://oauth2.googleapis.com/tokeninfo'),
];
