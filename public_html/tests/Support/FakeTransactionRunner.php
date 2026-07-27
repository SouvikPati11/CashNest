<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\TransactionRunnerInterface;

/**
 * Transaction runner double that executes the callback directly (no database).
 */
final class FakeTransactionRunner implements TransactionRunnerInterface
{
    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        return $callback();
    }
}
