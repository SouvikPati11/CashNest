<?php

declare(strict_types=1);

namespace Core;

/**
 * Configuration repository.
 *
 * Loads every PHP file in the `config/` directory into a single associative
 * store keyed by filename, then exposes dot-notation access
 * (e.g. `config('database.connections.mysql.host')`).
 */
final class Config
{
    /** @var array<string, mixed> */
    private array $items = [];

    /**
     * @param array<string, mixed> $items Pre-loaded configuration items.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Build a Config instance by requiring every `*.php` file in a directory.
     * Each file must return an array; it becomes a top-level key.
     */
    public static function fromDirectory(string $directory): self
    {
        $items = [];

        if (is_dir($directory)) {
            foreach (glob($directory . '/*.php') ?: [] as $file) {
                $name = basename($file, '.php');
                /** @psalm-suppress UnresolvableInclude */
                $data = require $file;

                if (is_array($data)) {
                    $items[$name] = $data;
                }
            }
        }

        return new self($items);
    }

    /**
     * Get a value using dot notation, returning $default when absent.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $default;
        }

        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        $segments = explode('.', $key);
        $value    = $this->items;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }

            return $default;
        }

        return $value;
    }

    /**
     * Overwrite a top-level configuration group at runtime (mainly for tests).
     */
    public function set(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    /**
     * Determine whether a dot-notation key exists.
     */
    public function has(string $key): bool
    {
        return $this->get($key, '__cashnest_missing__') !== '__cashnest_missing__';
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }
}
