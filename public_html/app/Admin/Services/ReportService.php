<?php

declare(strict_types=1);

namespace App\Admin\Services;

use App\Admin\Support\CsvExporter;
use App\Contracts\AdminQueryRepositoryInterface;
use App\Exceptions\NotFoundException;

/**
 * Reporting + CSV export for the admin panel.
 *
 * Report definitions are a trusted server-side allowlist of table + columns; the
 * generic query repository fetches rows (capped) and the CSV exporter renders
 * them, defusing formula injection.
 */
final class ReportService
{
    private const EXPORT_CAP = 5000;

    /**
     * @var array<string, array{title: string, table: string, columns: array<int, string>}>
     */
    private const REPORTS = [
        'users' => [
            'title'   => 'Users',
            'table'   => 'users',
            'columns' => ['id', 'uuid', 'name', 'email', 'status', 'created_at'],
        ],
        'withdrawals' => [
            'title'   => 'Withdrawals',
            'table'   => 'withdraw_requests',
            'columns' => ['id', 'uuid', 'user_id', 'coins_amount', 'net_amount', 'status', 'created_at'],
        ],
        'referrals' => [
            'title'   => 'Referrals',
            'table'   => 'referrals',
            'columns' => ['id', 'referrer_id', 'referee_id', 'status', 'created_at'],
        ],
        'audit' => [
            'title'   => 'Audit Logs',
            'table'   => 'admin_audit_logs',
            'columns' => ['id', 'admin_id', 'action', 'target_type', 'target_id', 'created_at'],
        ],
    ];

    public function __construct(
        private AdminQueryRepositoryInterface $query,
        private CsvExporter $csv
    ) {
    }

    /**
     * Available report definitions (key => title).
     *
     * @return array<string, string>
     */
    public function available(): array
    {
        return array_map(static fn(array $r): string => $r['title'], self::REPORTS);
    }

    /**
     * Render a report as CSV.
     *
     * @throws NotFoundException On an unknown report key.
     */
    public function exportCsv(string $key): string
    {
        $report = self::REPORTS[$key] ?? null;

        if ($report === null) {
            throw new NotFoundException('Unknown report.');
        }

        $rows = $this->query->paginate($report['table'], [], null, self::EXPORT_CAP, 0);

        return $this->csv->toCsv($report['columns'], $rows, $report['columns']);
    }

    public function has(string $key): bool
    {
        return isset(self::REPORTS[$key]);
    }
}
