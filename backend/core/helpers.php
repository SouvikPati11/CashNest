<?php

/**
 * Global helper functions.
 *
 * Kept intentionally minimal. Everything meaningful is resolved through the
 * service container; these are only tiny conveniences that are safe to call
 * anywhere. Each is guarded so re-declaration never fatals.
 */

declare(strict_types=1);

use Core\Env;

if (!function_exists('env')) {
    /**
     * Read an environment value with a default.
     */
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value (resolving closures).
     */
    function value(mixed $value): mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }
}
