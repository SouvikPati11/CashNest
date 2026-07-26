<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\WithdrawRequestRepositoryInterface;

/**
 * Withdraw request repository.
 *
 * Owns `withdraw_requests` and the append-only `withdraw_history` audit trail.
 * All balance movements happen in LedgerService, never here.
 */
final class WithdrawRequestRepository extends BaseRepository implements WithdrawRequestRepositoryInterface
{
    protected string $table = 'withdraw_requests';

    public function findByUuid(string $uuid): ?array
    {
        return $this->db->selectOne('SELECT * FROM `withdraw_requests` WHERE `uuid` = ? LIMIT 1', [$uuid]);
    }

    public function findByUuidForUser(string $uuid, int $userId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `withdraw_requests` WHERE `uuid` = ? AND `user_id` = ? LIMIT 1',
            [$uuid, $userId]
        );
    }

    public function findByHoldTransactionId(int $holdTransactionId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `withdraw_requests` WHERE `hold_transaction_id` = ? LIMIT 1',
            [$holdTransactionId]
        );
    }

    public function listForUser(int $userId, int $limit, int $offset, ?string $status): array
    {
        $where    = ['`user_id` = ?'];
        $bindings = [$userId];

        if ($status !== null && $status !== '') {
            $where[]    = '`status` = ?';
            $bindings[] = $status;
        }

        $sql = 'SELECT * FROM `withdraw_requests` WHERE ' . implode(' AND ', $where)
            . sprintf(' ORDER BY `id` DESC LIMIT %d OFFSET %d', $limit, $offset);

        return $this->db->select($sql, $bindings);
    }

    public function updateRequest(int $id, array $data): int
    {
        return $this->update($id, $data);
    }

    public function addHistory(array $data): string
    {
        $columns = array_keys($data);
        foreach ($columns as $column) {
            $this->assertIdentifier($column);
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList   = implode(', ', array_map(static fn(string $c): string => "`$c`", $columns));

        return $this->db->insert(
            sprintf('INSERT INTO `withdraw_history` (%s) VALUES (%s)', $columnList, $placeholders),
            array_values($data)
        );
    }

    public function historyForRequest(int $requestId): array
    {
        return $this->db->select(
            'SELECT `from_status`, `to_status`, `note`, `changed_by_admin_id`, `created_at`'
            . ' FROM `withdraw_history` WHERE `withdraw_request_id` = ? ORDER BY `id` ASC',
            [$requestId]
        );
    }
}
