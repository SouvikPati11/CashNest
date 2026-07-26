<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a route path exists but not for the requested HTTP method (405).
 *
 * Carries the list of methods that ARE allowed for the path so the router can
 * emit the required `Allow` header.
 */
final class MethodNotAllowedException extends HttpException
{
    /**
     * @param array<int, string> $allowedMethods Methods valid for the path.
     */
    public function __construct(private array $allowedMethods = [])
    {
        parent::__construct(405, 'METHOD_NOT_ALLOWED', 'The HTTP method is not allowed for this endpoint.');
    }

    /**
     * @return array<int, string>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
