<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Pagination;
use Core\Database\Database;

/**
 * Base repository.
 *
 * Encapsulates data access for a single table using parameterised queries only.
 * Feature repositories extend this and set `$table` (and optionally
 * `$primaryKey`). All identifiers (table/columns) used in generated SQL are
 * validated to a safe pattern to prevent injection through structural elements.
 */
abstract class BaseRepository
{
    /** Table name. Subclasses must set this. */
    protected string $table = '';

    /** Primary key column. */
    protected string $primaryKey = 'id';

    public function __construct(protected Database $db)
    {
    }

    /**
     * Find a row by primary key.
     *
     * @return array<string, mixed>|null
     */
    public function find(int|string $id): ?array
    {
        $this->assertIdentifier($this->table);
        $this->assertIdentifier($this->primaryKey);

        return $this->db->selectOne(
            sprintf('SELECT * FROM `%s` WHERE `%s` = ? LIMIT 1', $this->table, $this->primaryKey),
            [$id]
        );
    }

    /**
     * Find the first row matching a single column value.
     *
     * @return array<string, mixed>|null
     */
    public function findBy(string $column, mixed $value): ?array
    {
        $this->assertIdentifier($this->table);
        $this->assertIdentifier($column);

        return $this->db->selectOne(
            sprintf('SELECT * FROM `%s` WHERE `%s` = ? LIMIT 1', $this->table, $column),
            [$value]
        );
    }

    /**
     * Return a page of rows (offset mode) with newest first by primary key.
     *
     * @param array<string, mixed> $where Equality conditions (column => value).
     * @return array<int, array<string, mixed>>
     */
    public function paginate(Pagination $pagination, array $where = []): array
    {
        $this->assertIdentifier($this->table);

        [$clause, $bindings] = $this->buildWhere($where);

        $sql = sprintf(
            'SELECT * FROM `%s`%s ORDER BY `%s` DESC LIMIT %d OFFSET %d',
            $this->table,
            $clause,
            $this->primaryKey,
            $pagination->limit,
            $pagination->offset()
        );

        return $this->db->select($sql, $bindings);
    }

    /**
     * Count rows matching equality conditions.
     *
     * @param array<string, mixed> $where
     */
    public function count(array $where = []): int
    {
        $this->assertIdentifier($this->table);

        [$clause, $bindings] = $this->buildWhere($where);

        $row = $this->db->selectOne(
            sprintf('SELECT COUNT(*) AS aggregate FROM `%s`%s', $this->table, $clause),
            $bindings
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * Insert a row and return the new id.
     *
     * @param array<string, mixed> $data column => value.
     */
    public function create(array $data): string
    {
        $this->assertIdentifier($this->table);

        $columns = array_keys($data);

        foreach ($columns as $column) {
            $this->assertIdentifier($column);
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList   = implode(', ', array_map(static fn(string $c): string => "`$c`", $columns));

        $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table, $columnList, $placeholders);

        return $this->db->insert($sql, array_values($data));
    }

    /**
     * Update a row by primary key, returning affected row count.
     *
     * @param array<string, mixed> $data column => value.
     */
    public function update(int|string $id, array $data): int
    {
        $this->assertIdentifier($this->table);
        $this->assertIdentifier($this->primaryKey);

        if ($data === []) {
            return 0;
        }

        $assignments = [];
        $bindings    = [];

        foreach ($data as $column => $value) {
            $this->assertIdentifier($column);
            $assignments[] = "`$column` = ?";
            $bindings[]    = $value;
        }

        $bindings[] = $id;

        $sql = sprintf(
            'UPDATE `%s` SET %s WHERE `%s` = ?',
            $this->table,
            implode(', ', $assignments),
            $this->primaryKey
        );

        return $this->db->affectingStatement($sql, $bindings);
    }

    /**
     * Delete a row by primary key, returning affected row count.
     */
    public function delete(int|string $id): int
    {
        $this->assertIdentifier($this->table);
        $this->assertIdentifier($this->primaryKey);

        return $this->db->affectingStatement(
            sprintf('DELETE FROM `%s` WHERE `%s` = ?', $this->table, $this->primaryKey),
            [$id]
        );
    }

    /**
     * Build a WHERE clause from equality conditions.
     *
     * @param array<string, mixed> $where
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function buildWhere(array $where): array
    {
        if ($where === []) {
            return ['', []];
        }

        $conditions = [];
        $bindings   = [];

        foreach ($where as $column => $value) {
            $this->assertIdentifier($column);
            $conditions[] = "`$column` = ?";
            $bindings[]   = $value;
        }

        return [' WHERE ' . implode(' AND ', $conditions), $bindings];
    }

    /**
     * Guard against injection via structural identifiers (table/column names).
     */
    protected function assertIdentifier(string $identifier): void
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid SQL identifier: "%s".', $identifier));
        }
    }
}
