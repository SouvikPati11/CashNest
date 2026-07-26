<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\FraudFlagRepositoryInterface;

/**
 * In-memory fraud flag repository for DB-free service tests.
 */
final class InMemoryFraudFlagRepository implements FraudFlagRepositoryInterface
{
    /** @var array<int, bool> */
    public array $held = [];

    public function hold(int $userId): void
    {
        $this->held[$userId] = true;
    }

    public function hasActiveWithdrawHold(int $userId): bool
    {
        return $this->held[$userId] ?? false;
    }
}
