<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\DeviceTokenRepositoryInterface;

/**
 * In-memory device token repository for DB-free push-job tests.
 */
final class InMemoryDeviceTokenRepository implements DeviceTokenRepositoryInterface
{
    /** @var array<int, array<int, string>> */
    public array $tokens = [];

    /**
     * @param array<int, string> $tokens
     */
    public function setTokens(int $userId, array $tokens): void
    {
        $this->tokens[$userId] = $tokens;
    }

    public function activeTokensForUser(int $userId): array
    {
        return $this->tokens[$userId] ?? [];
    }
}
