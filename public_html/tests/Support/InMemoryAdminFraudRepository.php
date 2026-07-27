<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\AdminFraudRepositoryInterface;

/**
 * In-memory fraud repository for DB-free admin tests.
 */
final class InMemoryAdminFraudRepository implements AdminFraudRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    /**
     * @param array<string, mixed> $data
     */
    public function seed(array $data): int
    {
        $id = $this->nextId++;

        $this->rows[$id] = array_merge([
            'id'           => $id,
            'user_id'      => 1,
            'flag_type'    => 'manual',
            'severity'     => 'high',
            'status'       => 'open',
            'action_taken' => 'none',
        ], $data, ['id' => $id]);

        return $id;
    }

    public function paginate(?string $status, int $limit, int $offset): array
    {
        $rows = $this->filtered($status);
        usort($rows, static fn(array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return array_slice($rows, $offset, $limit);
    }

    public function countFiltered(?string $status): int
    {
        return count($this->filtered($status));
    }

    public function find(int $id): ?array
    {
        return $this->rows[$id] ?? null;
    }

    public function resolve(int $id, string $status, string $actionTaken, int $adminId, string $at): int
    {
        if (!isset($this->rows[$id])) {
            return 0;
        }

        $this->rows[$id] = array_merge($this->rows[$id], [
            'status'       => $status,
            'action_taken' => $actionTaken,
            'reviewed_by'  => $adminId,
            'reviewed_at'  => $at,
        ]);

        return 1;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filtered(?string $status): array
    {
        if ($status === null) {
            return array_values($this->rows);
        }

        return array_values(array_filter($this->rows, static fn(array $r): bool => ($r['status'] ?? null) === $status));
    }
}
