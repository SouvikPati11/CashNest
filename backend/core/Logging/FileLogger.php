<?php

declare(strict_types=1);

namespace Core\Logging;

use Core\Contracts\LoggerInterface;

/**
 * Append-only file logger.
 *
 * Writes daily-rotated, newline-delimited log lines with an ISO-8601 timestamp,
 * level, interpolated message, and JSON context. Chosen for shared hosting where
 * no external log service or daemon is available. A minimum level filters out
 * lower-severity noise.
 */
final class FileLogger implements LoggerInterface
{
    /** RFC 5424 severities, most to least verbose (higher value = more severe). */
    private const LEVELS = [
        'debug'     => 0,
        'info'      => 1,
        'notice'    => 2,
        'warning'   => 3,
        'error'     => 4,
        'critical'  => 5,
    ];

    private int $minLevel;

    public function __construct(
        private string $directory,
        string $minLevel = 'info'
    ) {
        $this->minLevel = self::LEVELS[strtolower($minLevel)] ?? self::LEVELS['info'];
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $level    = strtolower($level);
        $severity = self::LEVELS[$level] ?? self::LEVELS['info'];

        if ($severity < $this->minLevel) {
            return;
        }

        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }

        if (!is_writable($this->directory) && !is_writable(dirname($this->directory))) {
            return; // Never let logging break the request.
        }

        $line = sprintf(
            "[%s] %s: %s %s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            strtoupper($level),
            $this->interpolate($message, $context),
            $context === [] ? '' : $this->encodeContext($context)
        );

        $file = rtrim($this->directory, '/') . '/cashnest-' . gmdate('Y-m-d') . '.log';

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Replace `{key}` tokens in the message with scalar context values.
     *
     * @param array<string, mixed> $context
     */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];

        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $replacements['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replacements);
    }

    /**
     * Encode context to JSON, redacting obviously sensitive keys.
     *
     * @param array<string, mixed> $context
     */
    private function encodeContext(array $context): string
    {
        $redactKeys = ['password', 'token', 'secret', 'authorization', 'jwt', 'refresh_token'];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $redactKeys, true)) {
                $context[$key] = '[REDACTED]';
            }
        }

        $json = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? '{}' : $json;
    }
}
