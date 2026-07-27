<?php

declare(strict_types=1);

namespace Core\Contracts;

/**
 * Logger contract (PSR-3 compatible subset).
 *
 * Levels follow RFC 5424. Implementations must accept an interpolatable message
 * and a context array; `{placeholder}` tokens in the message are replaced with
 * matching context values.
 */
interface LoggerInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function log(string $level, string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function notice(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function critical(string $message, array $context = []): void;
}
