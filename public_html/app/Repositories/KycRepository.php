<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\KycRepositoryInterface;
use Core\Database\Database;

/**
 * KYC repository — reads `user_kyc` for the withdrawal verification gate.
 *
 * Not a BaseRepository subclass: it exposes only a single scoped read and never
 * touches the encrypted document columns.
 */
final class KycRepository implements KycRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->db->selectOne(
            'SELECT `id`, `user_id`, `status`, `reviewed_at` FROM `user_kyc` WHERE `user_id` = ? LIMIT 1',
            [$userId]
        );
    }
}
