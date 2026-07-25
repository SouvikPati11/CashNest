<?php

declare(strict_types=1);

namespace Core\Exceptions;

/**
 * Thrown when a JWT cannot be issued or verified.
 *
 * The `$reason` distinguishes an expired token from an otherwise invalid one so
 * the HTTP layer can map to TOKEN_EXPIRED vs INVALID_TOKEN.
 */
final class TokenException extends \RuntimeException
{
    public const REASON_INVALID = 'invalid';
    public const REASON_EXPIRED = 'expired';

    public function __construct(
        string $message,
        private string $reason = self::REASON_INVALID,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
