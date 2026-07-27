<?php

declare(strict_types=1);

use Core\Routing\Router;

/**
 * Postback routes (server-to-server callbacks).
 *
 * Reserved for signed offerwall / CPA / ad SSV callbacks. These are added with
 * the Offerwall and Ads modules and are authenticated via HMAC signature + IP
 * allowlist rather than JWT. No routes exist at the foundation stage.
 */
return static function (Router $router): void {
    // Intentionally empty until the Offerwall/Ads modules are built.
    unset($router);
};
