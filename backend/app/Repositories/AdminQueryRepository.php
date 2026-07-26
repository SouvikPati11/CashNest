<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\AdminQueryRepositoryInterface;
use Core\Database\Database;

/**
 * Generic admin query repository.
 *
 * Read-only, paginated access to allowlisted content/config tables for the admin
 * management browsers. Table and column names are validated to a strict
 * identifier pattern (they originate from the server-side resource registry, not
 * the client); search values are bound parameters — no user input reaches SQL
 * structurally.
 */
final class AdminQueryRepository implements AdminQueryRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    public function paginate(string $table, array $searchColumns, ?string $search, int $limit, int $offset): array
    {
        $this->assertIdentifier($table);

        [$where, $bindings] = $this->searchClause($searchColumns, $search);

        return $this->db->select(
            sprintf('SELECT * FROM `%s`%s ORDER BY `id` DESC LIMIT %d OFFSET %d', $table, $where, $limit, $offset),
            $bindings
        );
    }

    public function countRows(string $table, array $searchColumns, ?string $search): int
    {
        $this->assertIdentifier($table);

        [$where, $bindings] = $this->searchClause($searchColumns, $search);

        $row = $this->db->selectOne(
            sprintf('SELECT COUNT(*) AS aggregate FROM `%s`%s', $table, $where),
            $bindings
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * Build a LIKE search clause across the given columns.
     *
     * @param array<int, string> $searchColumns
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function searchClause(array $searchColumns, ?string $search): array
    {
        if ($search === null || $search === '' || $searchColumns === []) {
            return ['', []];
        }

        $terms    = [];
        $bindings = [];
        $like     = '%' . $search . '%';

        foreach ($searchColumns as $column) {
            $this->assertIdentifier($column);
            $terms[]    = sprintf('`%s` LIKE ?', $column);
            $bindings[] = $like;
        }

        return [' WHERE (' . implode(' OR ', $terms) . ')', $bindings];
    }

    /**
     * Guard structural identifiers (table/column names) against injection.
     */
    private function assertIdentifier(string $identifier): void
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid SQL identifier: "%s".', $identifier));
        }
    }
}
