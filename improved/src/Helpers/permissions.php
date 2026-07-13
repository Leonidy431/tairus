<?php
/**
 * Permission Helper Functions
 *
 * Provides convenient functions for checking user permissions throughout the application.
 */

/**
 * Check if current user has a specific permission
 */
function hasPermission(string $permissionName): bool
{
    // If no user session, return false
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Get database from globals
    if (!isset($GLOBALS['db'])) {
        return false;
    }

    $db = $GLOBALS['db'];
    $userId = $_SESSION['user_id'];

    $result = $db->selectOne(
        "SELECT rp.permission_id FROM user_roles ur
         JOIN role_permissions rp ON ur.role_id = rp.role_id
         JOIN permissions p ON rp.permission_id = p.id
         WHERE ur.user_id = ? AND p.name = ?",
        [$userId, $permissionName]
    );

    return $result !== null;
}

/**
 * Check if current user has specific role
 */
function hasRole(string $roleName): bool
{
    // If no user session, return false
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Get database from globals
    if (!isset($GLOBALS['db'])) {
        return false;
    }

    $db = $GLOBALS['db'];
    $userId = $_SESSION['user_id'];

    $result = $db->selectOne(
        "SELECT ur.user_id FROM user_roles ur
         JOIN roles r ON ur.role_id = r.id
         WHERE ur.user_id = ? AND r.name = ?",
        [$userId, $roleName]
    );

    return $result !== null;
}

/**
 * Check if current user is admin
 */
function isAdmin(): bool
{
    return hasRole('admin');
}

/**
 * Get all permissions of current user
 */
function getUserPermissions(): array
{
    if (!isset($_SESSION['user_id'])) {
        return [];
    }

    if (!isset($GLOBALS['db'])) {
        return [];
    }

    $db = $GLOBALS['db'];
    $userId = $_SESSION['user_id'];

    $permissions = $db->select(
        "SELECT DISTINCT p.name FROM user_roles ur
         JOIN role_permissions rp ON ur.role_id = rp.role_id
         JOIN permissions p ON rp.permission_id = p.id
         WHERE ur.user_id = ?",
        [$userId]
    );

    return array_map(fn($p) => $p['name'], $permissions);
}

/**
 * Get all roles of current user
 */
function getUserRoles(): array
{
    if (!isset($_SESSION['user_id'])) {
        return [];
    }

    if (!isset($GLOBALS['db'])) {
        return [];
    }

    $db = $GLOBALS['db'];
    $userId = $_SESSION['user_id'];

    $roles = $db->select(
        "SELECT r.name FROM user_roles ur
         JOIN roles r ON ur.role_id = r.id
         WHERE ur.user_id = ?",
        [$userId]
    );

    return array_map(fn($r) => $r['name'], $roles);
}

/**
 * Require specific permission or exit with error
 */
function requirePermission(string $permissionName, string $message = 'Access Denied'): void
{
    if (!hasPermission($permissionName)) {
        http_response_code(403);
        die(htmlspecialchars($message));
    }
}

/**
 * Require admin role or exit with error
 */
function requireAdmin(string $message = 'Admin access required'): void
{
    if (!isAdmin()) {
        http_response_code(403);
        die(htmlspecialchars($message));
    }
}

/**
 * Require specific role or exit with error
 */
function requireRole(string $roleName, string $message = 'Insufficient permissions'): void
{
    if (!hasRole($roleName)) {
        http_response_code(403);
        die(htmlspecialchars($message));
    }
}
