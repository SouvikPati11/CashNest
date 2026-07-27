<?php

declare(strict_types=1);

namespace App\Admin\Services;

use App\Contracts\AdminAuditLogRepositoryInterface;
use App\Admin\Support\Paginator;

/**
 * Admin audit logging.
 *
 * Records every mutating admin action to the append-only audit trail and serves
 * the filtered audit browser. Before/after snapshots are JSON-encoded.
 */
final class AuditLogService
{
    public function __construct(private AdminAuditLogRepositoryInterface $logs)
    {
    }

    /**
     * @param array<string, mixed> $options target_type, target_id, before, after, ip, user_agent
     */
    public function log(?int $adminId, string $action, array $options = []): void
    {
        $this->logs->create([
            'admin_id'    => $adminId,
            'action'      => $action,
            'target_type' => isset($options['target_type']) ? (string) $options['target_type'] : null,
            'target_id'   => isset($options['target_id']) ? (int) $options['target_id'] : null,
            'before_data' => $this->encode($options['before'] ?? null),
            'after_data'  => $this->encode($options['after'] ?? null),
            'ip_address'  => isset($options['ip']) ? (string) $options['ip'] : null,
            'user_agent'  => isset($options['user_agent']) ? substr((string) $options['user_agent'], 0, 255) : null,
        ]);
    }

    /**
     * A page of audit entries with its paginator.
     *
     * @return array{rows: array<int, array<string, mixed>>, paginator: Paginator}
     */
    public function recent(?string $action, ?int $adminId, int $page, int $perPage): array
    {
        $total     = $this->logs->countFiltered($action, $adminId);
        $paginator = new Paginator($total, $perPage, $page);
        $rows      = $this->logs->paginate($action, $adminId, $paginator->perPage, $paginator->offset());

        return ['rows' => $rows, 'paginator' => $paginator];
    }

    private function encode(mixed $value): ?string
    {
        if ($value === null || $value === []) {
            return null;
        }

        $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? null : $json;
    }
}
