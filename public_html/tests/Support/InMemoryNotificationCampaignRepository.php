<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\NotificationCampaignRepositoryInterface;

/**
 * In-memory notification campaign repository for DB-free service tests.
 */
final class InMemoryNotificationCampaignRepository implements NotificationCampaignRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    public function create(array $data): string
    {
        $id = $this->nextId++;

        $this->rows[$id] = array_merge($data, ['id' => $id]);

        return (string) $id;
    }

    public function find(int|string $id): ?array
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function update(int|string $id, array $data): int
    {
        $id = (int) $id;
        if (!isset($this->rows[$id])) {
            return 0;
        }

        $this->rows[$id] = array_merge($this->rows[$id], $data);

        return 1;
    }
}
