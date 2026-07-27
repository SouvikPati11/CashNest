<?php

declare(strict_types=1);

namespace App\Services;

use Core\Contracts\LoggerInterface;

/**
 * Base service.
 *
 * Services hold business logic and orchestrate repositories, other services, and
 * infrastructure. This base supplies a shared logger and a small helper for the
 * common "wrap in a database transaction" pattern used by feature services. No
 * concrete business logic exists yet.
 */
abstract class BaseService
{
    public function __construct(protected LoggerInterface $logger)
    {
    }
}
