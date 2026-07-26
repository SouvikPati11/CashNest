<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\WalletTransactionRepositoryInterface;

/**
 * In-memory ledger repository for DB-free tests. Enforces the reference_id
 * uniqueness constraint to mirror the database.
 */
final class InMemoryWalletTransactionRepository implements WalletTransactionRepositoryInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    private int $nextId = 1;

    public function find(int|string $id): ?array
    {
        return $this->rows[(int) $id] ?? null;
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->first('uuid', $uuid);
    }

    public function findByReferenceId(string $referenceId): ?array
    {
        return $this->first('reference_id', $referenceId);
    }

    public function findByUuidForUser(string $uuid, int $userId): ?array
    {
        foreach ($this->rows as $row) {
            if (($row['uuid'] ?? null) === $uuid && ($row['user_id'] ?? null) === $userId) {
                return $row;
            }
        }

        return null;
    }

    public function create(array $data): string
    {
        if (isset($data['reference_id']) && $this->first('reference_id', $data['reference_id']) !== null) {
            throw new \RuntimeException('Duplicate reference_id (unique constraint).');
        }

        $id = $this->nextId++;
        $this->rows[$id] = array_merge($data, [
            'id'         => $id,
            'created_at' => $data['created_at'] ?? gmdate('Y-m-d H:i:s'),
        ]);

        return (string) $id;
    }

    public function queryForUser(
        int $userId,
        array $filters,
        string $orderColumn,
        string $orderDir,
        int $limit,
        ?int $beforeId,
        int $offset
    ): array {
        $rows = array_values(array_filter($this->rows, function (array $row) use ($userId, $filters, $beforeId, $orderDir): bool {
            if (($row['user_id'] ?? null) !== $userId) {
                return false;
            }
            if (!empty($filters['type']) && ($row['type'] ?? null) !== $filters['type']) {
                return false;
            }
            if (!empty($filters['direction']) && ($row['direction'] ?? null) !== $filters['direction']) {
                return false;
            }
            if ($beforeId !== null) {
                $id = (int) $row['id'];
                if ($orderDir === 'DESC' ? $id >= $beforeId : $id <= $beforeId) {
                    return false;
                }
            }
            return true;
        }));

        $column = in_array($orderColumn, ['id', 'amount'], true) ? $orderColumn : 'id';
        usort($rows, static function (array $a, array $b) use ($column, $orderDir): int {
            $cmp = (int) $a[$column] <=> (int) $b[$column];
            return $orderDir === 'DESC' ? -$cmp : $cmp;
        });

        if ($beforeId === null && $offset > 0) {
            $rows = array_slice($rows, $offset);
        }

        return array_slice($rows, 0, $limit);
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
