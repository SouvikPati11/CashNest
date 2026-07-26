<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ReferralRepositoryInterface;

/**
 * Referral repository. Owns `referrals` and `referral_earnings`, and reads the
 * active `referral_config`.
 */
final class ReferralRepository extends BaseRepository implements ReferralRepositoryInterface
{
    protected string $table = 'referrals';

    public function findByReferee(int $refereeId): ?array
    {
        return $this->db->selectOne('SELECT * FROM `referrals` WHERE `referee_id` = ? LIMIT 1', [$refereeId]);
    }

    public function createReferral(array $data): string
    {
        return $this->create($data);
    }

    public function updateReferral(int $id, array $data): int
    {
        return $this->update($id, $data);
    }

    public function listForReferrer(int $referrerId, int $limit, int $offset, ?string $status): array
    {
        $where    = ['r.`referrer_id` = ?'];
        $bindings = [$referrerId];

        if ($status !== null && $status !== '') {
            $where[]    = 'r.`status` = ?';
            $bindings[] = $status;
        }

        $sql = 'SELECT r.`status`, r.`created_at`, u.`name` AS referee_name FROM `referrals` r'
            . ' INNER JOIN `users` u ON u.`id` = r.`referee_id`'
            . ' WHERE ' . implode(' AND ', $where)
            . sprintf(' ORDER BY r.`id` DESC LIMIT %d OFFSET %d', $limit, $offset);

        return $this->db->select($sql, $bindings);
    }

    public function countForReferrer(int $referrerId): int
    {
        $row = $this->db->selectOne(
            'SELECT COUNT(*) AS aggregate FROM `referrals` WHERE `referrer_id` = ?',
            [$referrerId]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function countForReferrerByStatus(int $referrerId, string $status): int
    {
        $row = $this->db->selectOne(
            'SELECT COUNT(*) AS aggregate FROM `referrals` WHERE `referrer_id` = ? AND `status` = ?',
            [$referrerId, $status]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function activeConfig(): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `referral_config` WHERE `is_active` = 1 ORDER BY `id` DESC LIMIT 1'
        );
    }

    public function createEarning(array $data): string
    {
        $columns = array_keys($data);
        foreach ($columns as $column) {
            $this->assertIdentifier($column);
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList   = implode(', ', array_map(static fn(string $c): string => "`$c`", $columns));

        return $this->db->insert(
            sprintf('INSERT INTO `referral_earnings` (%s) VALUES (%s)', $columnList, $placeholders),
            array_values($data)
        );
    }

    public function findEarningBySourceTxn(int $sourceTransactionId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `referral_earnings` WHERE `source_transaction_id` = ? LIMIT 1',
            [$sourceTransactionId]
        );
    }

    public function listEarningsForReferrer(int $referrerId, int $limit, int $offset): array
    {
        return $this->db->select(
            sprintf(
                'SELECT * FROM `referral_earnings` WHERE `referrer_id` = ? ORDER BY `id` DESC LIMIT %d OFFSET %d',
                $limit,
                $offset
            ),
            [$referrerId]
        );
    }

    public function sumEarningsForReferrer(int $referrerId): int
    {
        $row = $this->db->selectOne(
            'SELECT COALESCE(SUM(`commission_coins`), 0) AS total FROM `referral_earnings` WHERE `referrer_id` = ?',
            [$referrerId]
        );

        return (int) ($row['total'] ?? 0);
    }
}
