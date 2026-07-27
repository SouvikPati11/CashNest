<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AuthenticationRepositoryInterface;
use App\Models\AuthProvider;

/**
 * Authentication repository.
 *
 * Concrete data access for the `user_auth_providers` table. Inherits generic
 * CRUD from BaseRepository and adds the multi-column finders auth needs. All
 * queries are parameterised; table/column names are fixed literals.
 */
final class AuthenticationRepository extends BaseRepository implements AuthenticationRepositoryInterface
{
    protected string $table = 'user_auth_providers';

    protected string $primaryKey = 'id';

    public function findByProviderUid(string $provider, string $providerUid): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `user_auth_providers` WHERE `provider` = ? AND `provider_uid` = ? LIMIT 1',
            [$provider, $providerUid]
        );
    }

    public function findByUserAndProvider(int $userId, string $provider): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `user_auth_providers` WHERE `user_id` = ? AND `provider` = ? LIMIT 1',
            [$userId, $provider]
        );
    }

    public function findEmailProviderByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `user_auth_providers` WHERE `provider` = ? AND `email` = ? LIMIT 1',
            [AuthProvider::PROVIDER_EMAIL, $email]
        );
    }

    public function markUsed(int $id, string $timestamp): int
    {
        return $this->update($id, ['last_used_at' => $timestamp]);
    }
}
