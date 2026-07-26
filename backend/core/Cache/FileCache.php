<?php

declare(strict_types=1);

namespace Core\Cache;

use Core\Contracts\CacheInterface;

/**
 * Filesystem cache driver.
 *
 * Serializes each entry to a file named by a hash of its key, alongside an
 * expiry timestamp. Suitable for shared hosting (no daemon required). Uses file
 * locking for the increment path so concurrent rate-limit counters stay correct
 * enough for throttling purposes.
 */
final class FileCache implements CacheInterface
{
    /** 1-in-N chance that a write triggers a garbage-collection sweep. */
    private const GC_DIVISOR = 100;

    public function __construct(private string $directory)
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $entry = $this->read($key);

        return $entry === null ? $default : $entry['value'];
    }

    public function put(string $key, mixed $value, int $ttlSeconds = 0): bool
    {
        // 0 = persist forever; positive = future expiry; negative = already expired.
        $expires = $ttlSeconds !== 0 ? time() + $ttlSeconds : 0;

        $payload = serialize(['expires' => $expires, 'value' => $value]);

        $written = @file_put_contents($this->path($key), $payload, LOCK_EX) !== false;

        // Probabilistically sweep expired files so short-TTL keys (e.g. rate-limit
        // counters) never accumulate unbounded on shared hosting.
        if ($written && random_int(1, self::GC_DIVISOR) === 1) {
            $this->gc();
        }

        return $written;
    }

    /**
     * Remove expired cache files. Cheap best-effort sweep, invoked probabilistically.
     */
    public function gc(): int
    {
        $now     = time();
        $removed = 0;

        foreach (glob($this->directory . '/*.cache') ?: [] as $file) {
            $contents = @file_get_contents($file);

            if ($contents === false || $contents === '') {
                continue;
            }

            $entry = @unserialize($contents);

            if (!is_array($entry) || !array_key_exists('expires', $entry)) {
                continue;
            }

            $expires = (int) $entry['expires'];

            if ($expires !== 0 && $expires <= $now && @unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    public function add(string $key, mixed $value, int $ttlSeconds = 0): bool
    {
        if ($this->has($key)) {
            return false;
        }

        return $this->put($key, $value, $ttlSeconds);
    }

    public function has(string $key): bool
    {
        return $this->read($key) !== null;
    }

    public function increment(string $key, int $by = 1, int $ttlSeconds = 0): int
    {
        $path   = $this->path($key);
        $handle = fopen($path, 'c+');

        if ($handle === false) {
            return 0;
        }

        try {
            flock($handle, LOCK_EX);

            $contents = stream_get_contents($handle);
            $entry    = $contents !== false && $contents !== ''
                ? @unserialize($contents)
                : null;

            $now     = time();
            $current = 0;
            $expires = $ttlSeconds > 0 ? $now + $ttlSeconds : 0;

            if (is_array($entry) && ($entry['expires'] === 0 || $entry['expires'] > $now)) {
                $current = (int) $entry['value'];
                $expires = $entry['expires']; // preserve original window
            }

            $newValue = $current + $by;

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, serialize(['expires' => $expires, 'value' => $newValue]));
            fflush($handle);

            return $newValue;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function forget(string $key): bool
    {
        $path = $this->path($key);

        return !is_file($path) || @unlink($path);
    }

    public function flush(): bool
    {
        foreach (glob($this->directory . '/*.cache') ?: [] as $file) {
            @unlink($file);
        }

        return true;
    }

    /**
     * Read and validate an entry, deleting it if expired.
     *
     * @return array{expires: int, value: mixed}|null
     */
    private function read(string $key): ?array
    {
        $path = $this->path($key);

        if (!is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);

        if ($contents === false || $contents === '') {
            return null;
        }

        $entry = @unserialize($contents);

        if (!is_array($entry) || !array_key_exists('expires', $entry)) {
            return null;
        }

        $expires = (int) $entry['expires'];

        if ($expires !== 0 && $expires <= time()) {
            @unlink($path);
            return null;
        }

        return ['expires' => $expires, 'value' => $entry['value'] ?? null];
    }

    /**
     * Absolute cache-file path for a key.
     */
    private function path(string $key): string
    {
        return rtrim($this->directory, '/') . '/' . sha1($key) . '.cache';
    }
}
