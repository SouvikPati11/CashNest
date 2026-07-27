<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\MaintenanceRepositoryInterface;
use App\Contracts\MaintenanceServiceInterface;
use App\Models\MaintenanceWindow;

/**
 * Maintenance service — evaluates the maintenance gate server-side.
 *
 * The gate is active when the current window is enabled AND (any configured
 * schedule window contains now). Evaluated on the server so clients cannot
 * bypass it; the status itself is always readable.
 */
final class MaintenanceService implements MaintenanceServiceInterface
{
    public function __construct(private MaintenanceRepositoryInterface $maintenance)
    {
    }

    public function status(): array
    {
        $row = $this->maintenance->current();

        if ($row === null) {
            return [
                'enabled'         => false,
                'title'           => null,
                'message'         => null,
                'scheduled_start' => null,
                'scheduled_end'   => null,
            ];
        }

        $window = MaintenanceWindow::fromRow($row);
        $now    = gmdate('Y-m-d H:i:s');

        $start = is_string($row['scheduled_start'] ?? null) ? $row['scheduled_start'] : null;
        $end   = is_string($row['scheduled_end'] ?? null) ? $row['scheduled_end'] : null;

        $withinWindow = ($start === null || $now >= $start) && ($end === null || $now <= $end);

        return [
            'enabled'         => $window->isEnabled() && $withinWindow,
            'title'           => $row['title'] ?? null,
            'message'         => $row['message'] ?? null,
            'scheduled_start' => $start,
            'scheduled_end'   => $end,
        ];
    }
}
