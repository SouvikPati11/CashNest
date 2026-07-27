<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\WithdrawRequestRepositoryInterface;

/**
 * In-memory withdraw request repository for DB-free service tests. Tracks
 * requests and the append-only history audit trail.
 */
final class InMemoryWithdrawRequestRepository implements WithdrawRequestRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    /** @var array<int, array<string, mixed>> */
    public array $history = [];

    private int $nextId = 1;

    private int $nextHistoryId = 1;

    public function create(array $data): string
    {
        $id = $this->nextId++;

        $this->rows[$id] = array_merge($data, [
            'id'         => $id,
            'created_at' => $data['created_at'] ?? gmdate('Y-m-d H:i:s'),
        ]);

        return (string) $id;
    }

    public function find(int|string $id): ?array
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->first('uuid', $uuid);
    }

    public function findByUuidForUser(string $uuid, int $userId): ?array
    {
        foreach ($this->rows as $row) {
            if (($row['uuid'] ?? null) === $uuid && (int) ($row['user_id'] ?? 0) === $userId) {
                return $row;
            }
        }

        return null;
    }

    public function findByHoldTransactionId(int $holdTransactionId): ?array
    {
        foreach ($this->rows as $row) {
            if ((int) ($row['hold_transaction_id'] ?? 0) === $holdTransactionId) {
                return $row;
            }
        }

        return null;
    }

    public function listForUser(int $userId, int $limit, int $offset, ?string $status): array
    {
        $rows = array_values(array_filter($this->rows, static function (array $r) use ($userId, $status): bool {
            if ((int) ($r['user_id'] ?? 0) !== $userId) {
                return false;
            }

            return $status === null || $status === '' || ($r['status'] ?? null) === $status;
        }));

        usort($rows, static fn(array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        return array_slice($rows, $offset, $limit);
    }

    public function updateRequest(int $id, array $data): int
    {
        if (!isset($this->rows[$id])) {
            return 0;
        }

        $this->rows[$id] = array_merge($this->rows[$id], $data);

        return 1;
    }

    public function addHistory(array $data): string
    {
        $id = $this->nextHistoryId++;

        $this->history[$id] = array_merge($data, [
            'id'         => $id,
            'created_at' => $data['created_at'] ?? gmdate('Y-m-d H:i:s'),
        ]);

        return (string) $id;
    }

    public function historyForRequest(int $requestId): array
    {
        $rows = array_values(array_filter(
            $this->history,
            static fn(array $h): bool => (int) ($h['withdraw_request_id'] ?? 0) === $requestId
        ));

        usort($rows, static fn(array $a, array $b): int => (int) $a['id'] <=> (int) $b['id']);

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function first(string $column, mixed $value): ?array
    {
        foreach ($this->rows as $row) {
            if (($row[$column] ?? null) === $value) {
                return $row;
            }
        }

        return null;
    }
}
