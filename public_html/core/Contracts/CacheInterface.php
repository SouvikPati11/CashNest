<?php

declare(strict_types=1);

namespace Core\Contracts;

/**
 * Cache contract.
 *
 * A small key/value store with TTL support. The default file driver makes this
 * usable on shared hosting; the interface allows swapping to Redis/Memcached
 * later without touching callers (Dependency Inversion).
 */
interface CacheInterface
{
    /**
     * Retrieve an item, or $default when missing/expired.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item for a number of seconds (0 = persist until flushed).
     */
    public function put(string $key, mixed $value, int $ttlSeconds = 0): bool;

    /**
     * Store an item only if the key does not already exist.
     */
    public function add(string $key, mixed $value, int $ttlSeconds = 0): bool;

    /**
     * Whether a non-expired item exists.
     */
    public function has(string $key): bool;

    /**
     * Atomically increment an integer counter, returning the new value.
     */
    public function increment(string $key, int $by = 1, int $ttlSeconds = 0): int;

    /**
     * Remove an item.
     */
    public function forget(string $key): bool;

    /**
     * Clear the entire cache store.
     */
    public function flush(): bool;
}
