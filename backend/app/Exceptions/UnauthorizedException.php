<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when authentication is missing or invalid (HTTP 401).
 */
final class UnauthorizedException extends HttpException
{
    public function __construct(
        string $message = 'Authentication is required.',
        string $errorCode = 'AUTH_REQUIRED'
    ) {
        parent::__construct(401, $errorCode, $message);
    }
}
