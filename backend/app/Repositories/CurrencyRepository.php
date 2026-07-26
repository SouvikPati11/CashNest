<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\CurrencyRepositoryInterface;
use Core\Database\Database;

/**
 * Currency settings repository.
 *
 * Reads the single active row from `currency_settings`. Not a BaseRepository
 * subclass because it exposes no generic CRUD — only the active-row read.
 */
final class CurrencyRepository implements CurrencyRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function getActive(): ?array
    {
        return $this->db->selectOne(
            'SELECT * FROM `currency_settings` WHERE `is_active` = 1 ORDER BY `id` DESC LIMIT 1'
        );
    }
}
