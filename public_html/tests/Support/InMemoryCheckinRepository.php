<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\CheckinRepositoryInterface;

final class InMemoryCheckinRepository implements CheckinRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array<int, array<string, mixed>> */
    public array $ladder = [];

    private int $nextId = 1;

    public function findByUserAndDate(int $userId, string $date): ?array
    {
        foreach ($this->rows as $row) {
            if ($row['user_id'] === $userId && $row['checkin_date'] === $date) {
                return $row;
            }
        }

        return null;
    }

    public function findLatestForUser(int $userId): ?array
    {
        $latest = null;
        foreach ($this->rows as $row) {
            if ($row['user_id'] !== $userId) {
                continue;
            }
            if ($latest === null || strcmp((string) $row['checkin_date'], (string) $latest['checkin_date']) > 0) {
                $latest = $row;
            }
        }

        return $latest;
    }

    public function create(array $data): string
    {
        $id = $this->nextId++;
        $this->rows[$id] = $data + ['id' => $id];

        return (string) $id;
    }

    public function activeLadder(): array
    {
        return $this->ladder;
    }
}
