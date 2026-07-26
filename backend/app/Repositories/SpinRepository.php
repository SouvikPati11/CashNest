<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\SpinRepositoryInterface;

/**
 * Spin wheel repository. Owns `spin_history` and reads `spin_wheel_segments`.
 */
final class SpinRepository extends BaseRepository implements SpinRepositoryInterface
{
    protected string $table = 'spin_history';

    public function activeSegments(): array
    {
        return $this->db->select(
            'SELECT * FROM `spin_wheel_segments` WHERE `is_active` = 1 ORDER BY `position` ASC'
        );
    }

    public function findSegment(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM `spin_wheel_segments` WHERE `id` = ? LIMIT 1', [$id]);
    }

    public function createSpin(array $data): string
    {
        return $this->create($data);
    }

    public function updateSpin(int $id, array $data): int
    {
        return $this->update($id, $data);
    }

    public function countSpinsOnDate(int $userId, string $date): int
    {
        $row = $this->db->selectOne(
            'SELECT COUNT(*) AS aggregate FROM `spin_history` WHERE `user_id` = ? AND `spin_date` = ?',
            [$userId, $date]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function historyForUser(int $userId, int $limit, int $offset): array
    {
        return $this->db->select(
            sprintf(
                'SELECT * FROM `spin_history` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT %d OFFSET %d',
                $limit,
                $offset
            ),
            [$userId]
        );
    }
}
