<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Generic admin query repository contract.
 *
 * Powers read-only management browsers over content/config tables. The table and
 * searchable columns are supplied from a trusted server-side resource registry
 * (never user input) and validated as SQL identifiers; the search term is always
 * bound as a parameter.
 */
interface AdminQueryRepositoryInterface
{
    /**
     * A page of rows from an allowlisted table, newest first.
     *
     * @param array<int, string> $searchColumns
     * @return array<int, array<string, mixed>>
     */
    public function paginate(string $table, array $searchColumns, ?string $search, int $limit, int $offset): array;

    /**
     * @param array<int, string> $searchColumns
     */
    public function countRows(string $table, array $searchColumns, ?string $search): int;
}
