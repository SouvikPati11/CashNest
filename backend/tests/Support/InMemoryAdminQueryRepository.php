<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\AdminQueryRepositoryInterface;

/**
 * In-memory generic query repository for DB-free admin browser tests.
 */
final class InMemoryAdminQueryRepository implements AdminQueryRepositoryInterface
{
    /** @var array<string, array<int, array<string, mixed>>> */
    public array $tables = [];

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function seed(string $table, array $rows): void
    {
        $this->tables[$table] = $rows;
    }

    public function paginate(string $table, array $searchColumns, ?string $search, int $limit, int $offset): array
    {
        $rows = $this->match($table, $searchColumns, $search);
        usort($rows, static fn(array $a, array $b): int => (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0));

        return array_slice($rows, $offset, $limit);
    }

    public function countRows(string $table, array $searchColumns, ?string $search): int
    {
        return count($this->match($table, $searchColumns, $search));
    }

    /**
     * @param array<int, string> $searchColumns
     * @return array<int, array<string, mixed>>
     */
    private function match(string $table, array $searchColumns, ?string $search): array
    {
        $rows = $this->tables[$table] ?? [];

        if ($search === null || $search === '' || $searchColumns === []) {
            return array_values($rows);
        }

        $needle = strtolower($search);

        return array_values(array_filter($rows, static function (array $row) use ($searchColumns, $needle): bool {
            foreach ($searchColumns as $column) {
                if (str_contains(strtolower((string) ($row[$column] ?? '')), $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }
}
