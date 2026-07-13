<?php
/**
 * Admin Middleware
 *
 * Middleware to check if user has admin role and required permissions.
 * Protects admin routes from unauthorized access.
 */

namespace App\Middleware;

use App\Database\Database;

class AdminMiddleware
{
    private Database $db;
    private int $userId;
    private array $requiredPermissions;

    public function __construct(Database $db, int $userId, array $requiredPermissions = [])
    {
        $this->db = $db;
        $this->userId = $userId;
        $this->requiredPermissions = $requiredPermissions;
    }

    /**
     * Check if user has admin role
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $roleName): bool
    {
        $result = $this->db->selectOne(
            "SELECT ur.user_id FROM user_roles ur
             JOIN roles r ON ur.role_id = r.id
             WHERE ur.user_id = ? AND r.name = ?",
            [$this->userId, $roleName]
        );

        return $result !== null;
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permissionName): bool
    {
        $result = $this->db->selectOne(
            "SELECT rp.permission_id FROM user_roles ur
             JOIN role_permissions rp ON ur.role_id = rp.role_id
             JOIN permissions p ON rp.permission_id = p.id
             WHERE ur.user_id = ? AND p.name = ?",
            [$this->userId, $permissionName]
        );

        return $result !== null;
    }

    /**
     * Check if user has all required permissions
     */
    public function authorize(): bool
    {
        if (empty($this->requiredPermissions)) {
            return $this->isAdmin();
        }

        foreach ($this->requiredPermissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all user permissions
     */
    public function getPermissions(): array
    {
        $permissions = $this->db->select(
            "SELECT DISTINCT p.name FROM user_roles ur
             JOIN role_permissions rp ON ur.role_id = rp.role_id
             JOIN permissions p ON rp.permission_id = p.id
             WHERE ur.user_id = ?",
            [$this->userId]
        );

        return array_map(fn($p) => $p['name'], $permissions);
    }

    /**
     * Get user roles
     */
    public function getRoles(): array
    {
        $roles = $this->db->select(
            "SELECT r.name FROM user_roles ur
             JOIN roles r ON ur.role_id = r.id
             WHERE ur.user_id = ?",
            [$this->userId]
        );

        return array_map(fn($r) => $r['name'], $roles);
    }

    /**
     * Deny access and redirect with error
     */
    public static function deny(string $message = 'Access Denied'): void
    {
        http_response_code(403);
        die($message);
    }

    /**
     * Log admin action
     */
    public function logActivity(string $action, string $entityType, int $entityId = null, array $changes = []): void
    {
        $this->db->insert('admin_activity_logs', [
            'admin_id' => $this->userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'changes' => json_encode($changes),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
