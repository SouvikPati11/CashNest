<?php

declare(strict_types=1);

namespace App\Admin\Support;

/**
 * Admin session contract.
 *
 * Abstracts the session store so authentication, CSRF, and flash messaging are
 * testable without PHP's global session. Production uses PhpSession; tests use
 * an in-memory double.
 */
interface SessionInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function has(string $key): bool;

    public function forget(string $key): void;

    /** Clear all session data (e.g. on logout). */
    public function clear(): void;

    /** Regenerate the session id, preserving data (post-login fixation guard). */
    public function regenerate(): void;
}
