<?php
/**
 * RBAC (Role-Based Access Control) Unit Tests
 *
 * Tests for role permissions, user authorization, and access control.
 */

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use App\Database\Database;
use App\Middleware\AdminMiddleware;
use App\Repository\RoleRepository;

class RbacTest extends TestCase
{
    private Database $db;
    private AdminMiddleware $middleware;
    private RoleRepository $roleRepository;
    private int $testUserId = 999;
    private int $testRoleId = 1;
    private int $testPermissionId = 1;

    protected function setUp(): void
    {
        // Initialize database connection
        $this->db = new Database([
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? 3306,
            'database' => $_ENV['DB_NAME'] ?? 'test_db',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
        ]);

        $this->middleware = new AdminMiddleware($this->db, $this->testUserId);
        $this->roleRepository = new RoleRepository($this->db);

        // Create test data
        $this->setupTestData();
    }

    /**
     * Setup test data in database
     */
    private function setupTestData(): void
    {
        try {
            // Create test user
            $this->db->insert('users', [
                'id' => $this->testUserId,
                'name' => 'Test Admin',
                'email' => 'testadmin@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            // User might already exist
        }
    }

    /**
     * Test Case 1: User can be assigned a role
     *
     * This test verifies that a user can be successfully assigned to a role.
     */
    public function testUserCanBeAssignedToRole(): void
    {
        $roleId = 1; // Admin role
        $userId = $this->testUserId;

        $result = $this->roleRepository->assignToUser($userId, $roleId);

        $this->assertTrue(
            $result,
            'User should be successfully assigned to a role'
        );

        // Verify assignment
        $userRoles = $this->db->select(
            "SELECT * FROM user_roles WHERE user_id = ? AND role_id = ?",
            [$userId, $roleId]
        );

        $this->assertNotEmpty(
            $userRoles,
            'User should have the assigned role in the database'
        );
    }

    /**
     * Test Case 2: Admin middleware correctly checks admin role
     *
     * This test verifies that the AdminMiddleware can correctly identify
     * whether a user has the admin role.
     */
    public function testAdminMiddlewareChecksAdminRole(): void
    {
        // Assign admin role to test user
        $this->roleRepository->assignToUser($this->testUserId, 1); // Assuming ID 1 is admin

        $middleware = new AdminMiddleware($this->db, $this->testUserId);
        $isAdmin = $middleware->isAdmin();

        $this->assertTrue(
            $isAdmin,
            'Middleware should correctly identify admin user'
        );
    }

    /**
     * Test Case 3: Permission check works correctly
     *
     * This test verifies that the permission checking logic correctly
     * determines if a user has a specific permission based on their roles.
     */
    public function testPermissionCheckWorksCorrectly(): void
    {
        // Assign admin role to test user
        $this->roleRepository->assignToUser($this->testUserId, 1);

        $middleware = new AdminMiddleware($this->db, $this->testUserId);
        $hasPermission = $middleware->hasPermission('view_dashboard');

        $this->assertTrue(
            $hasPermission,
            'Admin user should have view_dashboard permission'
        );
    }

    /**
     * Test Case 4: Non-admin user cannot access admin functions
     *
     * This test verifies that users without the admin role or specific
     * permissions cannot access protected admin functions.
     */
    public function testNonAdminUserCannotAccessAdminFunctions(): void
    {
        // Create a user with only 'user' role
        $userId = 1000;
        try {
            $this->db->insert('users', [
                'id' => $userId,
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'password' => password_hash('password123', PASSWORD_BCRYPT),
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            // User might already exist
        }

        // Assign user role
        $this->roleRepository->assignToUser($userId, 3); // Assuming ID 3 is 'user'

        $middleware = new AdminMiddleware($this->db, $userId);

        $isAdmin = $middleware->isAdmin();
        $hasManagePermission = $middleware->hasPermission('manage_products');

        $this->assertFalse(
            $isAdmin,
            'Regular user should not be identified as admin'
        );

        $this->assertFalse(
            $hasManagePermission,
            'Regular user should not have manage_products permission'
        );
    }

    /**
     * Test role retrieval
     */
    public function testRoleRetrievalByName(): void
    {
        $role = $this->roleRepository->findByName('admin');

        $this->assertIsArray($role, 'Role should be returned as array');
        $this->assertArrayHasKey('id', $role, 'Role should have id');
        $this->assertArrayHasKey('name', $role, 'Role should have name');
        $this->assertEquals('admin', $role['name'], 'Role name should be admin');
    }

    /**
     * Test permission assignment to role
     */
    public function testPermissionAssignmentToRole(): void
    {
        $roleId = 1; // Admin role
        $permissionId = 1; // view_dashboard permission

        $result = $this->roleRepository->assignPermission($roleId, $permissionId);

        // Result should be true or the permission might already be assigned
        $this->assertTrue(
            $result || $this->roleRepository->hasPermission($roleId, $permissionId),
            'Permission should be assigned to role or already exist'
        );
    }

    /**
     * Test getting permissions for a role
     */
    public function testGetPermissionsForRole(): void
    {
        $roleId = 1; // Admin role

        $permissions = $this->roleRepository->getPermissions($roleId);

        $this->assertIsArray($permissions, 'Should return array of permissions');

        if (!empty($permissions)) {
            $firstPermission = $permissions[0];
            $this->assertArrayHasKey('name', $firstPermission, 'Permission should have name');
        }
    }

    /**
     * Cleanup after tests
     */
    protected function tearDown(): void
    {
        try {
            // Clean up test data
            $this->db->delete('user_roles', "user_id IN (?, ?)", [$this->testUserId, 1000]);
            $this->db->delete('users', "id IN (?, ?)", [$this->testUserId, 1000]);
        } catch (\Exception $e) {
            // Cleanup might fail, that's okay
        }
    }
}
