<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Exceptions\DatabaseException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * PDO database layer.
 *
 * A thin, safe wrapper around a single MySQL/InnoDB connection. The connection
 * is established lazily on first use (cheap on shared hosting) and configured
 * for exceptions, real prepared statements, and utf8mb4. All queries flow
 * through prepared statements to prevent SQL injection.
 */
final class Database
{
    private ?PDO $pdo = null;

    /**
     * @param array<string, mixed> $config Connection settings (host, port, ...).
     */
    public function __construct(private array $config)
    {
    }

    /**
     * Get the underlying PDO connection, connecting on first call.
     */
    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $driver    = (string) ($this->config['driver'] ?? 'mysql');
        $host      = (string) ($this->config['host'] ?? '127.0.0.1');
        $port      = (int) ($this->config['port'] ?? 3306);
        $database  = (string) ($this->config['database'] ?? '');
        $charset   = (string) ($this->config['charset'] ?? 'utf8mb4');
        $username  = (string) ($this->config['username'] ?? '');
        $password  = (string) ($this->config['password'] ?? '');

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $driver,
            $host,
            $port,
            $database,
            $charset
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new DatabaseException('Database connection failed.', 0, $e);
        }

        return $this->pdo;
    }

    /**
     * Run a SELECT and return all rows.
     *
     * @param array<string|int, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $bindings = []): array
    {
        $statement = $this->run($sql, $bindings);

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $statement->fetchAll();

        return $rows;
    }

    /**
     * Run a SELECT and return the first row (or null).
     *
     * @param array<string|int, mixed> $bindings
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $bindings = []): ?array
    {
        $statement = $this->run($sql, $bindings);

        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Execute an INSERT and return the last inserted id.
     *
     * @param array<string|int, mixed> $bindings
     */
    public function insert(string $sql, array $bindings = []): string
    {
        $this->run($sql, $bindings);

        return $this->pdo()->lastInsertId();
    }

    /**
     * Execute an UPDATE/DELETE and return affected row count.
     *
     * @param array<string|int, mixed> $bindings
     */
    public function affectingStatement(string $sql, array $bindings = []): int
    {
        return $this->run($sql, $bindings)->rowCount();
    }

    /**
     * Execute a raw statement, returning success.
     *
     * @param array<string|int, mixed> $bindings
     */
    public function statement(string $sql, array $bindings = []): bool
    {
        return $this->run($sql, $bindings)->rowCount() >= 0;
    }

    /**
     * Run a closure inside a transaction, committing on success and rolling
     * back on any throwable. The closure receives this Database instance.
     *
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($this);
            $pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Prepare, bind, and execute a statement.
     *
     * @param array<string|int, mixed> $bindings
     */
    private function run(string $sql, array $bindings): PDOStatement
    {
        try {
            $statement = $this->pdo()->prepare($sql);
            $statement->execute($this->normalizeBindings($bindings));

            return $statement;
        } catch (PDOException $e) {
            throw new DatabaseException('Database query failed.', 0, $e);
        }
    }

    /**
     * Normalize bindings so booleans/nulls bind predictably.
     *
     * @param array<string|int, mixed> $bindings
     * @return array<string|int, mixed>
     */
    private function normalizeBindings(array $bindings): array
    {
        foreach ($bindings as $key => $value) {
            if (is_bool($value)) {
                $bindings[$key] = $value ? 1 : 0;
            }
        }

        return $bindings;
    }
}
