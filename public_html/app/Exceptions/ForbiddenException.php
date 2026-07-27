<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when an authenticated caller is not permitted to act (HTTP 403).
 */
final class ForbiddenException extends HttpException
{
    public function __construct(
        string $message = 'You are not allowed to perform this action.',
        string $errorCode = 'FORBIDDEN'
    ) {
        parent::__construct(403, $errorCode, $message);
    }
}
