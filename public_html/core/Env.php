<?php

declare(strict_types=1);

namespace Core;

/**
 * Environment loader.
 *
 * A tiny, dependency-free `.env` parser suitable for shared hosting. It reads a
 * `.env` file once and exposes typed accessors. Values are cached in a static
 * store; real OS environment variables (getenv / $_ENV / $_SERVER) take
 * precedence so hosting-panel variables can override the file.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $items = [];

    private static bool $loaded = false;

    /**
     * Load and parse a .env file. Missing files are ignored (production may set
     * variables through the hosting panel instead of a file).
     */
    public static function load(string $path): void
    {
        self::$loaded = true;

        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and blank lines.
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);

            $name  = trim($name);
            $value = self::normalizeValue(trim($value));

            if ($name === '') {
                continue;
            }

            self::$items[$name] = $value;
        }
    }

    /**
     * Retrieve an environment value with an optional default.
     *
     * OS/environment variables win over the parsed file. Recognises the literal
     * strings true/false/null/empty and casts them accordingly.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Prefer real environment first (hosting panel overrides).
        $osValue = getenv($key);
        if ($osValue !== false) {
            return self::cast($osValue);
        }

        if (array_key_exists($key, $_ENV)) {
            return self::cast((string) $_ENV[$key]);
        }

        if (array_key_exists($key, self::$items)) {
            return self::cast(self::$items[$key]);
        }

        return $default;
    }

    /**
     * Whether load() has been invoked at least once.
     */
    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    /**
     * Strip surrounding quotes and unescape a raw .env value.
     */
    private static function normalizeValue(string $value): string
    {
        $length = strlen($value);

        if ($length >= 2) {
            $first = $value[0];
            $last  = $value[$length - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);

                if ($first === '"') {
                    $value = str_replace(['\\n', '\\r', '\\"'], ["\n", "\r", '"'], $value);
                }
            }
        }

        return $value;
    }

    /**
     * Cast recognised literals to their PHP equivalents.
     */
    private static function cast(string $value): mixed
    {
        return match (strtolower($value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}
