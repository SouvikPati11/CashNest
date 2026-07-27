<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\WalletRepositoryInterface;

/**
 * Wallet repository.
 *
 * Concrete data access for `wallets`. Provides the `FOR UPDATE` lock the ledger
 * relies on to serialise per-user balance mutations.
 */
final class WalletRepository extends BaseRepository implements WalletRepositoryInterface
{
    protected string $table = 'wallets';

    protected string $primaryKey = 'id';

    public function findByUserId(int $userId): ?array
    {
        return $this->findBy('user_id', $userId);
    }

    public function lockByUserId(int $userId): ?array
    {
        // Row lock; only meaningful inside an open transaction.
        return $this->db->selectOne(
            'SELECT * FROM `wallets` WHERE `user_id` = ? LIMIT 1 FOR UPDATE',
            [$userId]
        );
    }

    public function applyBalances(int $id, array $data): int
    {
        return $this->update($id, $data);
    }
}
