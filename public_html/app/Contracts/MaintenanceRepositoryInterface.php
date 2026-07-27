<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Maintenance repository contract — reads the current `maintenance_windows` row.
 */
interface MaintenanceRepositoryInterface
{
    /**
     * The most recent maintenance window, or null when none is configured.
     *
     * @return array<string, mixed>|null
     */
    public function current(): ?array;
}
