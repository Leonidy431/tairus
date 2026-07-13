<?php
/**
 * Database Abstraction Layer
 *
 * Provides a modern, secure database interface using PDO with prepared statements.
 */

namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private PDO $connection;
    private array $config;
    private int $queryCount = 0;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect(): void
    {
        try {
            $dsn = $this->buildDSN();
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            if ($this->config['driver'] === 'mysql') {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$this->config['charset']}";
            }

            $this->connection = new PDO(
                $dsn,
                $this->config['username'] ?? null,
                $this->config['password'] ?? null,
                $options
            );
        } catch (PDOException $e) {
            throw new DatabaseException('Database connection failed: ' . $e->getMessage());
        }
    }

    private function buildDSN(): string
    {
        if ($this->config['driver'] === 'sqlite') {
            return sprintf('sqlite:%s', $this->config['path']);
        }

        return sprintf(
            '%s:host=%s;port=%d;dbname=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->config['database']
        );
    }

    public function select(string $query, array $bindings = []): array
    {
        return $this->execute($query, $bindings)->fetchAll();
    }

    public function selectOne(string $query, array $bindings = []): ?array
    {
        $result = $this->execute($query, $bindings)->fetch();
        return $result ?: null;
    }

    public function insert(string $table, array $data): bool
    {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');

        $query = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->escapeIdentifier($table),
            implode(', ', array_map([$this, 'escapeIdentifier'], $columns)),
            implode(', ', $placeholders)
        );

        return $this->execute($query, array_values($data))->rowCount() > 0;
    }

    public function update(string $table, array $data, array $conditions): bool
    {
        if (empty($conditions)) {
            throw new DatabaseException('Update requires at least one condition');
        }

        $updates = [];
        foreach (array_keys($data) as $column) {
            $updates[] = $this->escapeIdentifier($column) . ' = ?';
        }

        $where = [];
        foreach (array_keys($conditions) as $column) {
            $where[] = $this->escapeIdentifier($column) . ' = ?';
        }

        $query = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->escapeIdentifier($table),
            implode(', ', $updates),
            implode(' AND ', $where)
        );

        $bindings = array_merge(array_values($data), array_values($conditions));
        return $this->execute($query, $bindings)->rowCount() > 0;
    }

    public function delete(string $table, array $conditions): bool
    {
        if (empty($conditions)) {
            throw new DatabaseException('Delete requires at least one condition');
        }

        $where = [];
        foreach (array_keys($conditions) as $column) {
            $where[] = $this->escapeIdentifier($column) . ' = ?';
        }

        $query = sprintf(
            'DELETE FROM %s WHERE %s',
            $this->escapeIdentifier($table),
            implode(' AND ', $where)
        );

        return $this->execute($query, array_values($conditions))->rowCount() > 0;
    }

    public function count(string $table, array $conditions = []): int
    {
        $query = 'SELECT COUNT(*) as count FROM ' . $this->escapeIdentifier($table);

        if (!empty($conditions)) {
            $where = [];
            foreach (array_keys($conditions) as $column) {
                $where[] = $this->escapeIdentifier($column) . ' = ?';
            }
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        $result = $this->execute($query, array_values($conditions))->fetch();
        return (int)$result['count'] ?? 0;
    }

    public function transaction(callable $callback): mixed
    {
        $this->connection->beginTransaction();

        try {
            $result = $callback($this);
            $this->connection->commit();
            return $result;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    private function execute(string $query, array $bindings = [])
    {
        $this->queryCount++;

        try {
            $statement = $this->connection->prepare($query);
            $statement->execute($bindings);
            return $statement;
        } catch (PDOException $e) {
            throw new DatabaseException('Query execution failed: ' . $e->getMessage());
        }
    }

    private function escapeIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
