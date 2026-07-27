<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TransactionRunnerInterface;
use Core\Database\Database;

/**
 * Database-backed transaction runner.
 *
 * Adapts the foundation's PDO Database transaction helper to the argument-less
 * TransactionRunnerInterface callback so services depend on the abstraction, not
 * the concrete database.
 */
final class DatabaseTransactionRunner implements TransactionRunnerInterface
{
    public function __construct(private Database $db)
    {
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        return $this->db->transaction(static fn(Database $db) => $callback());
    }
}
