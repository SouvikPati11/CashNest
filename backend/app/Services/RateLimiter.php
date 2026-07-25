<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\CacheInterface;

/**
 * Fixed-window rate limiter.
 *
 * Backed by the cache store's atomic increment. Each unique key (typically
 * "route:user" or "route:ip") gets a counter that resets after the window.
 * Implements the standard from API_SPECIFICATION.md §1.12 and exposes the
 * numbers needed for the X-RateLimit-* / Retry-After headers.
 */
final class RateLimiter
{
    public function __construct(private CacheInterface $cache)
    {
    }

    /**
     * Register a hit and report whether it is within the limit.
     *
     * @param string $key         Unique limiter key.
     * @param int    $maxAttempts Allowed hits per window.
     * @param int    $windowSeconds Window length in seconds.
     */
    public function attempt(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $current = $this->cache->increment($this->prefix($key), 1, $windowSeconds);

        return $current <= $maxAttempts;
    }

    /**
     * Current hit count within the active window.
     */
    public function attempts(string $key): int
    {
        return (int) $this->cache->get($this->prefix($key), 0);
    }

    /**
     * Remaining attempts before the limit is hit.
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        return max(0, $maxAttempts - $this->attempts($key));
    }

    /**
     * Reset a limiter key (e.g. after a successful login).
     */
    public function clear(string $key): void
    {
        $this->cache->forget($this->prefix($key));
    }

    /**
     * Namespace limiter keys to avoid collisions with other cache entries.
     */
    private function prefix(string $key): string
    {
        return 'ratelimit:' . $key;
    }
}
