<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\MaintenanceRepositoryInterface;

/**
 * In-memory maintenance repository for DB-free service tests.
 */
final class InMemoryMaintenanceRepository implements MaintenanceRepositoryInterface
{
    /** @var array<string, mixed>|null */
    public ?array $window = null;

    public function current(): ?array
    {
        return $this->window;
    }
}
