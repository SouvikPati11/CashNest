<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\WalletTransactionRepositoryInterface;

/**
 * Wallet transaction (ledger) repository.
 *
 * Append-only data access for `wallet_transactions`. Reads are strictly
 * user-scoped; the history query whitelists order columns and parameterises all
 * filters.
 */
final class WalletTransactionRepository extends BaseRepository implements WalletTransactionRepositoryInterface
{
    protected string $table = 'wallet_transactions';

    protected string $primaryKey = 'id';

    public function findByUuid(string $uuid): ?array
    {
        return $this->findBy('uuid', $uuid);
    }

    public function findByReferenceId(string $referenceId): ?array
    {
        return $this->findBy('reference_id', $referenceId);
    }

    public function findByUuidForUser(string $uuid, int $userId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `wallet_transactions` WHERE `uuid` = ? AND `user_id` = ? LIMIT 1',
            [$uuid, $userId]
        );
    }

    public function queryForUser(
        int $userId,
        array $filters,
        string $orderColumn,
        string $orderDir,
        int $limit,
        ?int $beforeId,
        int $offset
    ): array {
        $column = in_array($orderColumn, ['id', 'amount'], true) ? $orderColumn : 'id';
        $dir    = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';

        $where    = ['`user_id` = ?'];
        $bindings = [$userId];

        if (isset($filters['type']) && is_string($filters['type']) && $filters['type'] !== '') {
            $where[]    = '`type` = ?';
            $bindings[] = $filters['type'];
        }

        if (isset($filters['direction']) && is_string($filters['direction']) && $filters['direction'] !== '') {
            $where[]    = '`direction` = ?';
            $bindings[] = $filters['direction'];
        }

        if (isset($filters['date_from']) && is_string($filters['date_from']) && $filters['date_from'] !== '') {
            $where[]    = '`created_at` >= ?';
            $bindings[] = $filters['date_from'];
        }

        if (isset($filters['date_to']) && is_string($filters['date_to']) && $filters['date_to'] !== '') {
            $where[]    = '`created_at` <= ?';
            $bindings[] = $filters['date_to'];
        }

        if ($beforeId !== null) {
            $where[]    = $dir === 'DESC' ? '`id` < ?' : '`id` > ?';
            $bindings[] = $beforeId;
        }

        $sql = 'SELECT * FROM `wallet_transactions` WHERE ' . implode(' AND ', $where)
            . sprintf(' ORDER BY `%s` %s, `id` %s LIMIT %d', $column, $dir, $dir, $limit);

        if ($beforeId === null && $offset > 0) {
            $sql .= ' OFFSET ' . $offset;
        }

        return $this->db->select($sql, $bindings);
    }
}
