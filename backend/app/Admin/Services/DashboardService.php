<?php

declare(strict_types=1);

namespace App\Admin\Services;

use App\Contracts\AdminFraudRepositoryInterface;
use App\Contracts\AdminQueryRepositoryInterface;

/**
 * Admin dashboard metrics.
 *
 * Aggregates headline counts across the platform and shapes a bar-chart dataset
 * for the dashboard. All counts come through repositories (no direct DB access
 * in the controller/view).
 */
final class DashboardService
{
    public function __construct(
        private AdminQueryRepositoryInterface $query,
        private AdminFraudRepositoryInterface $fraud
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function metrics(): array
    {
        $users         = $this->query->countRows('users', [], null);
        $wallets       = $this->query->countRows('wallets', [], null);
        $withdrawals   = $this->query->countRows('withdraw_requests', [], null);
        $notifications = $this->query->countRows('notifications', [], null);
        $openFraud     = $this->fraud->countFiltered('open');

        return [
            'cards' => [
                ['label' => 'Users', 'value' => $users, 'icon' => 'people'],
                ['label' => 'Wallets', 'value' => $wallets, 'icon' => 'wallet'],
                ['label' => 'Withdrawals', 'value' => $withdrawals, 'icon' => 'payments'],
                ['label' => 'Open Fraud Flags', 'value' => $openFraud, 'icon' => 'gpp_maybe'],
            ],
            'chart' => [
                'labels' => ['Users', 'Wallets', 'Withdrawals', 'Notifications'],
                'values' => [$users, $wallets, $withdrawals, $notifications],
            ],
        ];
    }
}
