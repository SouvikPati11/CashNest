<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Transaction runner contract.
 *
 * Abstracts "run this closure inside a database transaction" so orchestration
 * services stay decoupled from the concrete database layer (Dependency
 * Inversion) and remain unit-testable without a live connection.
 */
interface TransactionRunnerInterface
{
    /**
     * Execute a callback atomically, returning its result.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed;
}
