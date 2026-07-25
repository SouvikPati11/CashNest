<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a rate limit is exceeded (HTTP 429).
 */
final class TooManyRequestsException extends HttpException
{
    /**
     * @param int $retryAfter Seconds until the caller may retry.
     */
    public function __construct(
        private int $retryAfter = 60,
        string $message = 'Too many requests. Please slow down.'
    ) {
        parent::__construct(429, 'RATE_LIMITED', $message);
    }

    /**
     * Seconds the client should wait before retrying (Retry-After header).
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
