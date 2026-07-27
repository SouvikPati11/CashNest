<?php

declare(strict_types=1);

namespace App\Admin\Support;

/**
 * PHP native session store, namespaced under a single key.
 *
 * Starts the session lazily and keeps all admin state inside `$_SESSION[$ns]`
 * so it never collides with other consumers.
 */
final class PhpSession implements SessionInterface
{
    public function __construct(private string $namespace = 'cashnest_admin')
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->boot();

        return $_SESSION[$this->namespace][$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->boot();
        $_SESSION[$this->namespace][$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->boot();

        return isset($_SESSION[$this->namespace][$key]);
    }

    public function forget(string $key): void
    {
        $this->boot();
        unset($_SESSION[$this->namespace][$key]);
    }

    public function clear(): void
    {
        $this->boot();
        $_SESSION[$this->namespace] = [];
    }

    public function regenerate(): void
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    private function boot(): void
    {
        if (PHP_SAPI === 'cli') {
            // No real session in CLI/tests; back onto the superglobal array.
            $_SESSION[$this->namespace] ??= [];

            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION[$this->namespace] ??= [];
    }
}
