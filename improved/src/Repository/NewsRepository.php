<?php

namespace App\Repository;

class NewsRepository extends Repository
{
    protected string $table = 'news';

    public function getPublished(int $page = 1, int $perPage = 10): array
    {
        return $this->paginate($page, $perPage, ['created_at' => 'desc']);
    }

    public function getUnsubscribed(): array
    {
        return $this->db->select(
            "SELECT * FROM {$this->table} WHERE is_subscribed = 0 AND is_public = 1 ORDER BY created_at DESC"
        );
    }

    public function markAsSubscribed(int $id): bool
    {
        return $this->db->update($this->table, ['is_subscribed' => 1], ['id' => $id]);
    }

    public function markAllSubscribed(): bool
    {
        return $this->db->getConnection()->exec(
            "UPDATE {$this->table} SET is_subscribed = 1 WHERE is_subscribed = 0 AND is_public = 1"
        ) !== false;
    }
}
