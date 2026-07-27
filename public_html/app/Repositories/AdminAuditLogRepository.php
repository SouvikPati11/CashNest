<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AdminAuditLogRepositoryInterface;
use Core\Database\Database;

/**
 * Admin audit log repository — append-only writes and filtered reads of
 * `admin_audit_logs`.
 */
final class AdminAuditLogRepository implements AdminAuditLogRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function create(array $data): string
    {
        return $this->db->insert(
            'INSERT INTO `admin_audit_logs`'
            . ' (`admin_id`, `action`, `target_type`, `target_id`, `before_data`, `after_data`,'
            . ' `ip_address`, `user_agent`)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['admin_id'] ?? null,
                (string) ($data['action'] ?? ''),
                $data['target_type'] ?? null,
                $data['target_id'] ?? null,
                $data['before_data'] ?? null,
                $data['after_data'] ?? null,
                $data['ip_address'] ?? null,
                $data['user_agent'] ?? null,
            ]
        );
    }

    public function paginate(?string $action, ?int $adminId, int $limit, int $offset): array
    {
        [$where, $bindings] = $this->filters($action, $adminId);

        return $this->db->select(
            'SELECT * FROM `admin_audit_logs`' . $where
            . sprintf(' ORDER BY `id` DESC LIMIT %d OFFSET %d', $limit, $offset),
            $bindings
        );
    }

    public function countFiltered(?string $action, ?int $adminId): int
    {
        [$where, $bindings] = $this->filters($action, $adminId);

        $row = $this->db->selectOne('SELECT COUNT(*) AS aggregate FROM `admin_audit_logs`' . $where, $bindings);

        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function filters(?string $action, ?int $adminId): array
    {
        $conditions = [];
        $bindings   = [];

        if ($action !== null && $action !== '') {
            $conditions[] = '`action` = ?';
            $bindings[]   = $action;
        }

        if ($adminId !== null) {
            $conditions[] = '`admin_id` = ?';
            $bindings[]   = $adminId;
        }

        $where = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return [$where, $bindings];
    }
}
