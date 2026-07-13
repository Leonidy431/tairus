<?php
/**
 * User Repository
 *
 * Handles all user-related database operations including authentication and profile management.
 */

namespace App\Repository;

use App\Database\Database;

class UserRepository extends Repository
{
    protected string $table = 'users';

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );
    }

    /**
     * Create new user
     */
    public function create(array $data): int
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        return $this->save($data);
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $data['id'] = $id;
        $this->save($data);
        return true;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Verify user password
     */
    public function verifyPassword(string $email, string $password): bool
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return false;
        }

        return password_verify($password, $user['password']);
    }

    /**
     * Activate user
     */
    public function activate(int $userId): bool
    {
        return $this->db->update($this->table, ['is_active' => 1], ['id' => $userId]);
    }

    /**
     * Deactivate user
     */
    public function deactivate(int $userId): bool
    {
        return $this->db->update($this->table, ['is_active' => 0], ['id' => $userId]);
    }

    /**
     * Update last login time
     */
    public function updateLastLogin(int $userId): bool
    {
        return $this->db->update($this->table, ['last_login' => date('Y-m-d H:i:s')], ['id' => $userId]);
    }

    /**
     * Get active users count
     */
    public function countActiveUsers(): int
    {
        return $this->count(['is_active' => 1]);
    }

    /**
     * Get users with roles
     */
    public function getUsersWithRoles(int $limit = null, int $offset = 0): array
    {
        $query = "SELECT u.*, GROUP_CONCAT(r.name) as roles
                  FROM {$this->table} u
                  LEFT JOIN user_roles ur ON u.id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.id
                  GROUP BY u.id
                  ORDER BY u.name";

        if ($limit !== null) {
            $query .= " LIMIT ? OFFSET ?";
            return $this->db->select($query, [$limit, $offset]);
        }

        return $this->db->select($query);
    }

    /**
     * Get user roles
     */
    public function getRoles(int $userId): array
    {
        return $this->db->select(
            "SELECT r.* FROM roles r
             JOIN user_roles ur ON r.id = ur.role_id
             WHERE ur.user_id = ?
             ORDER BY r.name",
            [$userId]
        );
    }

    /**
     * Get user permissions
     */
    public function getPermissions(int $userId): array
    {
        return $this->db->select(
            "SELECT DISTINCT p.* FROM permissions p
             JOIN role_permissions rp ON p.id = rp.permission_id
             JOIN user_roles ur ON rp.role_id = ur.role_id
             WHERE ur.user_id = ?
             ORDER BY p.name",
            [$userId]
        );
    }
}
