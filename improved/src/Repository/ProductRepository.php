<?php

namespace App\Repository;

class ProductRepository extends Repository
{
    protected string $table = 'products';

    public function getByCategory(string $category, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE category = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$category, $perPage, $offset]
        );
    }

    public function getBySubcategory(string $category, string $subcategory, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE category = ? AND subcategory = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$category, $subcategory, $perPage, $offset]
        );
    }

    public function getFeatured(int $limit = 5): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE is_featured = 1 AND is_public = 1 ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    public function search(string $term, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $searchTerm = "%{$term}%";

        return $this->db->select(
            "SELECT * FROM {$this->table}
             WHERE (title LIKE ? OR description LIKE ?) AND is_public = 1
             ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$searchTerm, $searchTerm, $perPage, $offset]
        );
    }

    public function getPublished(): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE is_public = 1 ORDER BY created_at DESC"
        );
    }

    public function countByCategory(string $category): int
    {
        return $this->db->count($this->table, ['category' => $category, 'is_public' => 1]);
    }
}
