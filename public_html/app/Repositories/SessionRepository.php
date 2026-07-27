<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\SessionRepositoryInterface;

/**
 * Session repository.
 *
 * Concrete data access for `user_sessions`. Inherits parameterised CRUD from
 * BaseRepository and adds refresh-hash lookup and bulk revocation.
 */
final class SessionRepository extends BaseRepository implements SessionRepositoryInterface
{
    protected string $table = 'user_sessions';

    protected string $primaryKey = 'id';

    public function findByRefreshHash(string $hash): ?array
    {
        return $this->findBy('refresh_token_hash', $hash);
    }

    public function revoke(int $id, string $timestamp): int
    {
        return $this->update($id, ['revoked_at' => $timestamp]);
    }

    public function revokeAllForUser(int $userId, string $timestamp): int
    {
        return $this->db->affectingStatement(
            'UPDATE `user_sessions` SET `revoked_at` = ? WHERE `user_id` = ? AND `revoked_at` IS NULL',
            [$timestamp, $userId]
        );
    }
}
