<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AdminFraudRepositoryInterface;
use Core\Database\Database;

/**
 * Admin fraud repository — reads and resolves `fraud_flags`.
 */
final class AdminFraudRepository implements AdminFraudRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function paginate(?string $status, int $limit, int $offset): array
    {
        [$where, $bindings] = $this->filter($status);

        return $this->db->select(
            'SELECT * FROM `fraud_flags`' . $where
            . sprintf(' ORDER BY `id` DESC LIMIT %d OFFSET %d', $limit, $offset),
            $bindings
        );
    }

    public function countFiltered(?string $status): int
    {
        [$where, $bindings] = $this->filter($status);

        $row = $this->db->selectOne('SELECT COUNT(*) AS aggregate FROM `fraud_flags`' . $where, $bindings);

        return (int) ($row['aggregate'] ?? 0);
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM `fraud_flags` WHERE `id` = ? LIMIT 1', [$id]);
    }

    public function resolve(int $id, string $status, string $actionTaken, int $adminId, string $at): int
    {
        return $this->db->affectingStatement(
            'UPDATE `fraud_flags` SET `status` = ?, `action_taken` = ?, `reviewed_by` = ?, `reviewed_at` = ?'
            . ' WHERE `id` = ?',
            [$status, $actionTaken, $adminId, $at, $id]
        );
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function filter(?string $status): array
    {
        if ($status === null || $status === '') {
            return ['', []];
        }

        return [' WHERE `status` = ?', [$status]];
    }
}
