<?php
/**
 * Base Repository Class
 *
 * Provides common database operations for entities.
 */

namespace App\Repository;

use App\Database\Database;

abstract class Repository
{
    protected Database $db;
    protected string $table;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    public function all(array $orderBy = []): array
    {
        $query = "SELECT * FROM {$this->table}";

        if (!empty($orderBy)) {
            $order = [];
            foreach ($orderBy as $column => $direction) {
                $order[] = "`{$column}` " . strtoupper($direction);
            }
            $query .= " ORDER BY " . implode(', ', $order);
        }

        return $this->db->select($query);
    }

    public function paginate(int $page = 1, int $perPage = 10, array $orderBy = ['id' => 'desc']): array
    {
        $offset = ($page - 1) * $perPage;
        $order = [];

        foreach ($orderBy as $column => $direction) {
            $order[] = "`{$column}` " . strtoupper($direction);
        }

        $query = "SELECT * FROM {$this->table}";

        if (!empty($order)) {
            $query .= " ORDER BY " . implode(', ', $order);
        }

        $query .= " LIMIT ? OFFSET ?";

        return $this->db->select($query, [$perPage, $offset]);
    }

    public function save(array $data): int
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $this->db->update($this->table, $data, ['id' => $id]);
            return $id;
        }

        $this->db->insert($this->table, $data);

        // Get last insert ID
        return (int)$this->db->getConnection()->lastInsertId();
    }

    public function delete(int $id): bool
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    public function count(array $conditions = []): int
    {
        return $this->db->count($this->table, $conditions);
    }

    public function where(array $conditions, array $orderBy = []): array
    {
        $where = [];
        $bindings = [];

        foreach ($conditions as $column => $value) {
            $where[] = "`{$column}` = ?";
            $bindings[] = $value;
        }

        $query = "SELECT * FROM {$this->table}";

        if (!empty($where)) {
            $query .= " WHERE " . implode(' AND ', $where);
        }

        if (!empty($orderBy)) {
            $order = [];
            foreach ($orderBy as $column => $direction) {
                $order[] = "`{$column}` " . strtoupper($direction);
            }
            $query .= " ORDER BY " . implode(', ', $order);
        }

        return $this->db->select($query, $bindings);
    }

    public function search(string $searchTerm, array $columns, array $orderBy = []): array
    {
        $where = [];
        $bindings = [];

        foreach ($columns as $column) {
            $where[] = "`{$column}` LIKE ?";
            $bindings[] = "%{$searchTerm}%";
        }

        $query = "SELECT * FROM {$this->table}";

        if (!empty($where)) {
            $query .= " WHERE " . implode(' OR ', $where);
        }

        if (!empty($orderBy)) {
            $order = [];
            foreach ($orderBy as $column => $direction) {
                $order[] = "`{$column}` " . strtoupper($direction);
            }
            $query .= " ORDER BY " . implode(', ', $order);
        }

        return $this->db->select($query, $bindings);
    }
}
