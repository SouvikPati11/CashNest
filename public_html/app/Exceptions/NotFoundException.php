<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a resource or route cannot be found (HTTP 404).
 */
final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Resource not found.')
    {
        parent::__construct(404, 'NOT_FOUND', $message);
    }
}
