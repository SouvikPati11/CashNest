<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\WithdrawMethodRepositoryInterface;

/**
 * Withdraw method repository — reads active `withdraw_methods`.
 */
final class WithdrawMethodRepository extends BaseRepository implements WithdrawMethodRepositoryInterface
{
    protected string $table = 'withdraw_methods';

    public function activeMethods(): array
    {
        return $this->db->select(
            'SELECT * FROM `withdraw_methods` WHERE `is_active` = 1 ORDER BY `sort_order` ASC, `id` ASC'
        );
    }

    public function findActiveByCode(string $code): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `withdraw_methods` WHERE `code` = ? AND `is_active` = 1 LIMIT 1',
            [$code]
        );
    }
}
