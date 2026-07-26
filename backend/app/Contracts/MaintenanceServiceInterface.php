<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Maintenance service contract — evaluates the maintenance gate.
 */
interface MaintenanceServiceInterface
{
    /**
     * Current maintenance status (enabled + message + schedule).
     *
     * @return array<string, mixed>
     */
    public function status(): array;
}
