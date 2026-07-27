<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Withdraw method repository contract — reads `withdraw_methods`.
 */
interface WithdrawMethodRepositoryInterface
{
    /**
     * All active methods ordered for display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeMethods(): array;

    /**
     * Find an active method by its machine code.
     *
     * @return array<string, mixed>|null
     */
    public function findActiveByCode(string $code): ?array;
}
