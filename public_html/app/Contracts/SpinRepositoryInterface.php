<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Spin wheel repository contract (spin_wheel_segments + spin_history).
 */
interface SpinRepositoryInterface
{
    /**
     * Active wheel segments ordered by position.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeSegments(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findSegment(int $id): ?array;

    /**
     * @param array<string, mixed> $data
     */
    public function createSpin(array $data): string;

    /**
     * @param array<string, mixed> $data
     */
    public function updateSpin(int $id, array $data): int;

    /**
     * Number of spins a user has performed on a given date (daily-limit check).
     */
    public function countSpinsOnDate(int $userId, string $date): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function historyForUser(int $userId, int $limit, int $offset): array;
}
