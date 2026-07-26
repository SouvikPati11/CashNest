<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Daily check-in repository contract (daily_checkins + checkin_rewards_config).
 */
interface CheckinRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findByUserAndDate(int $userId, string $date): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestForUser(int $userId): ?array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * Active reward ladder ordered by day number.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeLadder(): array;
}
