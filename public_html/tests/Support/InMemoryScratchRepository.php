<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\ScratchRepositoryInterface;

final class InMemoryScratchRepository implements ScratchRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $cards = [];

    /** @var array<int, array<string, mixed>> */
    public array $pool = [];

    private int $nextId = 1;

    public function seedCard(array $data): int
    {
        $id = $this->nextId++;
        $this->cards[$id] = array_merge(['id' => $id, 'status' => 'issued', 'source' => 'daily', 'reward_coins' => null], $data, ['id' => $id]);

        return $id;
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $card = $this->cards[$id] ?? null;

        return $card !== null && $card['user_id'] === $userId ? $card : null;
    }

    public function availableForUser(int $userId): array
    {
        return array_values(array_filter(
            $this->cards,
            static fn(array $c): bool => $c['user_id'] === $userId && in_array($c['status'], ['issued', 'revealed'], true)
        ));
    }

    public function create(array $data): string
    {
        return (string) $this->seedCard($data);
    }

    public function updateCard(int $id, array $data): int
    {
        if (!isset($this->cards[$id])) {
            return 0;
        }
        $this->cards[$id] = array_merge($this->cards[$id], $data);

        return 1;
    }

    public function historyForUser(int $userId, int $limit, int $offset, ?string $status): array
    {
        $rows = array_values(array_filter($this->cards, static function (array $c) use ($userId, $status): bool {
            return $c['user_id'] === $userId && ($status === null || $status === '' || $c['status'] === $status);
        }));

        usort($rows, static fn(array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return array_slice($rows, $offset, $limit);
    }

    public function activePool(): array
    {
        return $this->pool;
    }

    public function countWonTodayByReward(int $userId, int $rewardCoins, string $date): int
    {
        $count = 0;
        foreach ($this->cards as $c) {
            if (
                $c['user_id'] === $userId
                && (int) $c['reward_coins'] === $rewardCoins
                && in_array($c['status'], ['revealed', 'claimed'], true)
            ) {
                $count++;
            }
        }

        return $count;
    }
}
