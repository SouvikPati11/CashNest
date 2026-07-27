<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\CheckinRepositoryInterface;

/**
 * Daily check-in repository. Owns `daily_checkins` writes and reads the active
 * `checkin_rewards_config` ladder.
 */
final class CheckinRepository extends BaseRepository implements CheckinRepositoryInterface
{
    protected string $table = 'daily_checkins';

    public function findByUserAndDate(int $userId, string $date): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `daily_checkins` WHERE `user_id` = ? AND `checkin_date` = ? LIMIT 1',
            [$userId, $date]
        );
    }

    public function findLatestForUser(int $userId): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `daily_checkins` WHERE `user_id` = ? ORDER BY `checkin_date` DESC LIMIT 1',
            [$userId]
        );
    }

    public function activeLadder(): array
    {
        return $this->db->select(
            'SELECT * FROM `checkin_rewards_config` WHERE `is_active` = 1 ORDER BY `day_number` ASC'
        );
    }
}
