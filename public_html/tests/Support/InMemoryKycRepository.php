<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\KycRepositoryInterface;

/**
 * In-memory KYC repository for DB-free service tests.
 */
final class InMemoryKycRepository implements KycRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function set(int $userId, string $status): void
    {
        $this->rows[$userId] = ['user_id' => $userId, 'status' => $status, 'reviewed_at' => gmdate('Y-m-d H:i:s')];
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->rows[$userId] ?? null;
    }
}
