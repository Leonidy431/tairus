<?php
/**
 * Role Repository
 *
 * Handles all role-related database operations including permissions management.
 */

namespace App\Repository;

use App\Database\Database;

class RoleRepository extends Repository
{
    protected string $table = 'roles';

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Find role by name
     */
    public function findByName(string $name): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE name = ?",
            [$name]
        );
    }

    /**
     * Get role with its permissions
     */
    public function getWithPermissions(int $roleId): ?array
    {
        $role = $this->find($roleId);
        if (!$role) {
            return null;
        }

        $role['permissions'] = $this->getPermissions($roleId);
        return $role;
    }

    /**
     * Get all permissions assigned to a role
     */
    public function getPermissions(int $roleId): array
    {
        return $this->db->select(
            "SELECT p.* FROM permissions p
             JOIN role_permissions rp ON p.id = rp.permission_id
             WHERE rp.role_id = ?
             ORDER BY p.name",
            [$roleId]
        );
    }

    /**
     * Assign permission to role
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        try {
            $this->db->insert('role_permissions', [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Revoke permission from role
     */
    public function revokePermission(int $roleId, int $permissionId): bool
    {
        return $this->db->delete('role_permissions', [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
        ]);
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(int $roleId, int $permissionId): bool
    {
        $result = $this->db->selectOne(
            "SELECT id FROM role_permissions WHERE role_id = ? AND permission_id = ?",
            [$roleId, $permissionId]
        );

        return $result !== null;
    }

    /**
     * Get users assigned to a role
     */
    public function getUsers(int $roleId): array
    {
        return $this->db->select(
            "SELECT u.* FROM users u
             JOIN user_roles ur ON u.id = ur.user_id
             WHERE ur.role_id = ?
             ORDER BY u.name",
            [$roleId]
        );
    }

    /**
     * Assign role to user
     */
    public function assignToUser(int $userId, int $roleId): bool
    {
        try {
            $this->db->insert('user_roles', [
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove role from user
     */
    public function removeFromUser(int $userId, int $roleId): bool
    {
        return $this->db->delete('user_roles', [
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
    }

    /**
     * Count users with this role
     */
    public function countUsers(int $roleId): int
    {
        $result = $this->db->selectOne(
            "SELECT COUNT(user_id) as count FROM user_roles WHERE role_id = ?",
            [$roleId]
        );

        return $result['count'] ?? 0;
    }
}
