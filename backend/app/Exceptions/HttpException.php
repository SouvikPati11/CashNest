<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Base exception for any error that maps to an HTTP response.
 *
 * Carries the HTTP status, a machine error code (see API_SPECIFICATION.md §1.7),
 * and an optional list of structured field errors. The global exception handler
 * renders these into the standard error envelope.
 */
class HttpException extends \RuntimeException
{
    /**
     * @param int                                                         $statusCode HTTP status code.
     * @param string                                                      $errorCode  Machine-readable code.
     * @param string                                                      $message    Human-readable message.
     * @param array<int, array{code: string, field: ?string, message: string}> $errors Field-level errors.
     * @param \Throwable|null                                             $previous   Previous throwable.
     */
    public function __construct(
        protected int $statusCode = 500,
        protected string $errorCode = 'INTERNAL_ERROR',
        string $message = 'An unexpected error occurred.',
        protected array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * HTTP status code to return.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Machine-readable error code from the API standard set.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Structured field errors (may be empty).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
