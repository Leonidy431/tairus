<?php
/**
 * Permission Repository
 *
 * Handles all permission-related database operations.
 */

namespace App\Repository;

use App\Database\Database;

class PermissionRepository extends Repository
{
    protected string $table = 'permissions';

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    /**
     * Find permission by name
     */
    public function findByName(string $name): ?array
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE name = ?",
            [$name]
        );
    }

    /**
     * Get all permissions grouped by category
     */
    public function getAllGrouped(): array
    {
        $permissions = $this->all(['name' => 'asc']);

        $grouped = [];
        foreach ($permissions as $permission) {
            $category = explode('_', $permission['name'])[0];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }

        return $grouped;
    }

    /**
     * Check if permission exists
     */
    public function exists(string $name): bool
    {
        return $this->findByName($name) !== null;
    }

    /**
     * Get permissions by role
     */
    public function getByRole(int $roleId): array
    {
        return $this->db->select(
            "SELECT p.* FROM {$this->table} p
             JOIN role_permissions rp ON p.id = rp.permission_id
             WHERE rp.role_id = ?
             ORDER BY p.name",
            [$roleId]
        );
    }

    /**
     * Get roles having permission
     */
    public function getRoles(int $permissionId): array
    {
        return $this->db->select(
            "SELECT r.* FROM roles r
             JOIN role_permissions rp ON r.id = rp.role_id
             WHERE rp.permission_id = ?
             ORDER BY r.name",
            [$permissionId]
        );
    }
}
