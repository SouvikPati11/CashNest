<?php

declare(strict_types=1);

namespace Tests\Unit\Admin;

use App\Admin\Services\DashboardService;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryAdminFraudRepository;
use Tests\Support\InMemoryAdminQueryRepository;

final class DashboardServiceTest extends TestCase
{
    public function testAggregatesMetricsAndChart(): void
    {
        $query = new InMemoryAdminQueryRepository();
        $query->seed('users', [['id' => 1], ['id' => 2], ['id' => 3]]);
        $query->seed('wallets', [['id' => 1], ['id' => 2]]);
        $query->seed('withdraw_requests', [['id' => 1]]);
        $query->seed('notifications', []);

        $fraud = new InMemoryAdminFraudRepository();
        $fraud->seed(['status' => 'open']);
        $fraud->seed(['status' => 'open']);
        $fraud->seed(['status' => 'dismissed']);

        $metrics = (new DashboardService($query, $fraud))->metrics();

        $cards = [];
        foreach ($metrics['cards'] as $card) {
            $cards[$card['label']] = $card['value'];
        }

        self::assertSame(3, $cards['Users']);
        self::assertSame(2, $cards['Wallets']);
        self::assertSame(1, $cards['Withdrawals']);
        self::assertSame(2, $cards['Open Fraud Flags']);
        self::assertSame([3, 2, 1, 0], $metrics['chart']['values']);
    }
}
