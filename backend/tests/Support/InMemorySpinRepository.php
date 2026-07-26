<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\SpinRepositoryInterface;

final class InMemorySpinRepository implements SpinRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $segments = [];

    /** @var array<int, array<string, mixed>> */
    public array $spins = [];

    private int $nextSpinId = 1;

    public function activeSegments(): array
    {
        return $this->segments;
    }

    public function findSegment(int $id): ?array
    {
        foreach ($this->segments as $s) {
            if ((int) $s['id'] === $id) {
                return $s;
            }
        }

        return null;
    }

    public function createSpin(array $data): string
    {
        $id = $this->nextSpinId++;
        $this->spins[$id] = $data + ['id' => $id];

        return (string) $id;
    }

    public function updateSpin(int $id, array $data): int
    {
        if (!isset($this->spins[$id])) {
            return 0;
        }
        $this->spins[$id] = array_merge($this->spins[$id], $data);

        return 1;
    }

    public function countSpinsOnDate(int $userId, string $date): int
    {
        $count = 0;
        foreach ($this->spins as $s) {
            if ($s['user_id'] === $userId && $s['spin_date'] === $date) {
                $count++;
            }
        }

        return $count;
    }

    public function historyForUser(int $userId, int $limit, int $offset): array
    {
        $rows = array_values(array_filter($this->spins, static fn(array $s): bool => $s['user_id'] === $userId));
        usort($rows, static fn(array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return array_slice($rows, $offset, $limit);
    }
}
