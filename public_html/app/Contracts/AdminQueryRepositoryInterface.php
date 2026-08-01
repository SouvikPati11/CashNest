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

    /**
     * A single row from an allowlisted table by primary key, or null.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $table, int|string $id): ?array;

    /**
     * Update allowlisted columns of a single row by primary key.
     *
     * The caller (service) is responsible for restricting `$data` to a trusted
     * editable-column allowlist; column names are additionally validated as SQL
     * identifiers and values are always bound.
     *
     * @param array<string, mixed> $data
     * @return int rows affected
     */
    public function update(string $table, int|string $id, array $data): int;

    /**
     * Insert a row of allowlisted columns; returns the new id.
     *
     * The caller (service) restricts `$data` to a trusted column allowlist;
     * identifiers are validated and values bound.
     *
     * @param array<string, mixed> $data
     */
    public function insert(string $table, array $data): string;

    /**
     * Delete a single row by primary key.
     *
     * @return int rows affected
     */
    public function delete(string $table, int|string $id): int;
}
