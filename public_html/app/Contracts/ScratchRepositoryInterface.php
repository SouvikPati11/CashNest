<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Scratch card repository contract (scratch_cards + scratch_card_config).
 */
interface ScratchRepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function findForUser(int $id, int $userId): ?array;

    /**
     * Cards available to a user (issued or revealed, not yet claimed/expired).
     *
     * @return array<int, array<string, mixed>>
     */
    public function availableForUser(int $userId): array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string;

    /**
     * @param array<string, mixed> $data
     */
    public function updateCard(int $id, array $data): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function historyForUser(int $userId, int $limit, int $offset, ?string $status): array;

    /**
     * Active prize pool.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activePool(): array;

    /**
     * How many times a user has already won a given prize tier today (daily cap).
     */
    public function countWonTodayByReward(int $userId, int $rewardCoins, string $date): int;
}
