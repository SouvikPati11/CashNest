<?php

declare(strict_types=1);

namespace Tests\Support;

use Core\Contracts\LoggerInterface;

/**
 * No-op logger for unit tests.
 */
final class NullLogger implements LoggerInterface
{
    public function log(string $level, string $message, array $context = []): void
    {
    }

    public function debug(string $message, array $context = []): void
    {
    }

    public function info(string $message, array $context = []): void
    {
    }

    public function notice(string $message, array $context = []): void
    {
    }

    public function warning(string $message, array $context = []): void
    {
    }

    public function error(string $message, array $context = []): void
    {
    }

    public function critical(string $message, array $context = []): void
    {
    }
}
