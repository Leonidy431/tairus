<?php

namespace App\Repository;

class SubscriptionRepository extends Repository
{
    protected string $table = 'subscription';

    public function getByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );
    }

    public function subscribe(string $email): bool
    {
        // Check if already exists
        if ($this->getByEmail($email)) {
            return true; // Already subscribed
        }

        return $this->db->insert($this->table, [
            'email' => $email,
            'subscribed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function unsubscribe(string $email): bool
    {
        return $this->db->delete($this->table, ['email' => $email]);
    }

    public function getAllEmails(): array
    {
        $results = $this->db->select("SELECT email FROM {$this->table}");
        return array_column($results, 'email');
    }
}
