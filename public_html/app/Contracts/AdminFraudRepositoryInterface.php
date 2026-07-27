<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Admin fraud repository contract — reads/resolves `fraud_flags`.
 */
interface AdminFraudRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function paginate(?string $status, int $limit, int $offset): array;

    public function countFiltered(?string $status): int;

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array;

    /**
     * Resolve a flag (review outcome).
     */
    public function resolve(int $id, string $status, string $actionTaken, int $adminId, string $at): int;
}
