<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\ScratchRepositoryInterface;

/**
 * Scratch card repository. Owns `scratch_cards` and reads `scratch_card_config`.
 */
final class ScratchRepository extends BaseRepository implements ScratchRepositoryInterface
{
    protected string $table = 'scratch_cards';

    public function findForUser(int $id, int $userId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `scratch_cards` WHERE `id` = ? AND `user_id` = ? LIMIT 1',
            [$id, $userId]
        );
    }

    public function availableForUser(int $userId): array
    {
        return $this->db->select(
            "SELECT * FROM `scratch_cards`
             WHERE `user_id` = ? AND `status` IN ('issued','revealed')
             ORDER BY `id` DESC",
            [$userId]
        );
    }

    public function updateCard(int $id, array $data): int
    {
        return $this->update($id, $data);
    }

    public function historyForUser(int $userId, int $limit, int $offset, ?string $status): array
    {
        $where    = ['`user_id` = ?'];
        $bindings = [$userId];

        if ($status !== null && $status !== '') {
            $where[]    = '`status` = ?';
            $bindings[] = $status;
        }

        $sql = 'SELECT * FROM `scratch_cards` WHERE ' . implode(' AND ', $where)
            . sprintf(' ORDER BY `id` DESC LIMIT %d OFFSET %d', $limit, $offset);

        return $this->db->select($sql, $bindings);
    }

    public function activePool(): array
    {
        return $this->db->select('SELECT * FROM `scratch_card_config` WHERE `is_active` = 1');
    }

    public function countWonTodayByReward(int $userId, int $rewardCoins, string $date): int
    {
        $row = $this->db->selectOne(
            "SELECT COUNT(*) AS aggregate FROM `scratch_cards`
             WHERE `user_id` = ? AND `reward_coins` = ? AND `status` IN ('revealed','claimed')
             AND `revealed_at` >= ?",
            [$userId, $rewardCoins, $date . ' 00:00:00']
        );

        return (int) ($row['aggregate'] ?? 0);
    }
}
