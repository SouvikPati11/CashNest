<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Admin\Support\SessionInterface;

/**
 * In-memory session store for DB/session-free admin tests.
 */
final class ArraySession implements SessionInterface
{
    /** @var array<string, mixed> */
    public array $data = [];

    public int $regenerated = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function regenerate(): void
    {
        $this->regenerated++;
    }
}
