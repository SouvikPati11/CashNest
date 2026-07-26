<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AdminRepositoryInterface;
use Core\Database\Database;

/**
 * Admin account repository — reads/updates `admins`.
 */
final class AdminRepository implements AdminRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function findActiveByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `admins` WHERE `email` = ? AND `status` = \'active\' AND `deleted_at` IS NULL LIMIT 1',
            [$email]
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne('SELECT * FROM `admins` WHERE `id` = ? LIMIT 1', [$id]);
    }

    public function recordLogin(int $id, string $ip, string $at): void
    {
        $this->db->affectingStatement(
            'UPDATE `admins` SET `last_login_at` = ?, `last_login_ip` = ? WHERE `id` = ?',
            [$at, $ip, $id]
        );
    }
}
