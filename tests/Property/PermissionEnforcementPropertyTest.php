<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\RoleService;
use Karyalay\Services\AuthService;
use Karyalay\Models\User;

/**
 * Property-based tests for permission enforcement
 * 
 * Feature: karyalay-portal-system, Property 46: Permission Enforcement
 * Validates: Requirements 13.6
 */
class PermissionEnforcementPropertyTest extends TestCase
{
    use TestTrait;

    private AuthService $authService;
    private User $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
        $this->userModel = new User();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 46: Permission Enforcement
     * 
     * For any admin role with assigned permissions, when an action is performed,
     * the system should enforce those permissions and deny actions not permitted.
     * 
     * Validates: Requirements 13.6
     * 
     * @test
     */
    public function rolesHaveCorrectPermissions(): void
    {
        $this->forAll(
            Generator\elements(['ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR'])
        )
        ->then(function ($role) {
            // Get permissions for this role
            $permissions = RoleService::getPermissions($role);
            
            // Assert: Role should have at least one permission
            $this->assertNotEmpty(
                $permissions,
                "Role {$role} should have at least one permission"
            );
            
            // Assert: Each permission should be a string
            foreach ($permissions as $permission) {
                $this->assertIsString(
                    $permission,
                    "Permission should be a string"
                );
                
                // Assert: Permission should follow format "category.action"
                $this->assertMatchesRegularExpression(
                    '/^[a-z_]+\.[a-z_]+$/',
                    $permission,
                    "Permission should follow format 'category.action'"
                );
            }
        });
    }

    /**
     * Property: Admin role has all permissions
     * 
     * For any permission in the system, the ADMIN role should have it.
     * 
     * @test
     */
    public function adminRoleHasAllPermissions(): void
    {
        $allPermissions = RoleService::getAllPermissions();
        $adminPermissions = RoleService::getPermissions('ADMIN');
        
        // Assert: Admin should have all permissions
        foreach ($allPermissions as $permission) {
            $this->assertContains(
                $permission,
                $adminPermissions,
                "ADMIN role should have permission: {$permission}"
            );
        }
    }

    /**
     * Property: Non-admin roles have subset of admin permissions
     * 
     * For any non-admin role, all its permissions should be a subset of admin permissions.
     * 
     * @test
     */
    public function nonAdminRolesHaveSubsetOfAdminPermissions(): void
    {
        $this->forAll(
            Generator\elements(['SUPPORT', 'SALES', 'CONTENT_EDITOR'])
        )
        ->then(function ($role) {
            $adminPermissions = RoleService::getPermissions('ADMIN');
            $rolePermissions = RoleService::getPermissions($role);
            
            // Assert: All role permissions should be in admin permissions
            foreach ($rolePermissions as $permission) {
                $this->assertContains(
                    $permission,
                    $adminPermissions,
                    "Permission {$permission} for role {$role} should be in ADMIN permissions"
                );
            }
        });
    }

    /**
     * Property: Permission checking is consistent
     * 
     * For any role and permission, checking the same permission multiple times
     * should return the same result.
     * 
     * @test
     */
    public function permissionCheckingIsConsistent(): void
    {
        $this->forAll(
            Generator\elements(['ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR']),
            Generator\elements(['users.view', 'tickets.view', 'content.edit', 'orders.view'])
        )
        ->then(function ($role, $permission) {
            // Check permission multiple times
            $check1 = RoleService::hasPermission($role, $permission);
            $check2 = RoleService::hasPermission($role, $permission);
            $check3 = RoleService::hasPermission($role, $permission);
            
            // Assert: All checks should return same result
            $this->assertEquals(
                $check1,
                $check2,
                "Multiple permission checks should return consistent results"
            );
            $this->assertEquals(
                $check2,
                $check3,
                "Multiple permission checks should return consistent results"
            );
        });
    }

    /**
     * Property: User permission checking works correctly
     * 
     * For any user with a role, the user should have exactly the permissions
     * defined for that role.
     * 
     * @test
     */
    public function userPermissionCheckingWorksCorrectly(): void
    {
        $this->forAll(
            Generator\elements(['ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR'])
        )
        ->then(function ($role) {
            // Create a test user with specific role
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            $this->cleanupUserByEmail($email);
            
            $user = $this->createUserWithRole($email, $role);
            
            if (!$user) {
                return; // Skip if user creation failed
            }
            
            // Get permissions for this role
            $rolePermissions = RoleService::getPermissions($role);
            
            // Assert: User should have all role permissions
            foreach ($rolePermissions as $permission) {
                $this->assertTrue(
                    RoleService::userHasPermission($user, $permission),
                    "User with role {$role} should have permission: {$permission}"
                );
            }
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Users without a role have no permissions
     * 
     * @test
     */
    public function usersWithoutRoleHaveNoPermissions(): void
    {
        $user = ['id' => '123', 'email' => 'test@example.com', 'name' => 'Test'];
        
        // User without role key
        $this->assertFalse(
            RoleService::userHasPermission($user, 'users.view'),
            'User without role should not have any permissions'
        );
        
        // User with empty role
        $user['role'] = '';
        $this->assertFalse(
            RoleService::userHasPermission($user, 'users.view'),
            'User with empty role should not have any permissions'
        );
        
        // User with invalid role
        $user['role'] = 'INVALID_ROLE';
        $this->assertFalse(
            RoleService::userHasPermission($user, 'users.view'),
            'User with invalid role should not have any permissions'
        );
    }

    /**
     * Property: Support role has limited permissions
     * 
     * Support role should have ticket and customer view permissions,
     * but not user management or settings permissions.
     * 
     * @test
     */
    public function supportRoleHasLimitedPermissions(): void
    {
        $supportPermissions = RoleService::getPermissions('SUPPORT');
        
        // Assert: Support should have ticket permissions
        $this->assertContains(
            'tickets.view',
            $supportPermissions,
            'SUPPORT role should have tickets.view permission'
        );
        $this->assertContains(
            'tickets.edit',
            $supportPermissions,
            'SUPPORT role should have tickets.edit permission'
        );
        
        // Assert: Support should have customer view permission
        $this->assertContains(
            'customers.view',
            $supportPermissions,
            'SUPPORT role should have customers.view permission'
        );
        
        // Assert: Support should NOT have user management permissions
        $this->assertNotContains(
            'users.create',
            $supportPermissions,
            'SUPPORT role should not have users.create permission'
        );
        $this->assertNotContains(
            'users.delete',
            $supportPermissions,
            'SUPPORT role should not have users.delete permission'
        );
        
        // Assert: Support should NOT have settings permissions
        $this->assertNotContains(
            'settings.edit',
            $supportPermissions,
            'SUPPORT role should not have settings.edit permission'
        );
    }

    /**
     * Property: Sales role has appropriate permissions
     * 
     * Sales role should have lead and customer management permissions,
     * but not content or port management permissions.
     * 
     * @test
     */
    public function salesRoleHasAppropriatePermissions(): void
    {
        $salesPermissions = RoleService::getPermissions('SALES');
        
        // Assert: Sales should have lead permissions
        $this->assertContains(
            'leads.view',
            $salesPermissions,
            'SALES role should have leads.view permission'
        );
        $this->assertContains(
            'leads.edit',
            $salesPermissions,
            'SALES role should have leads.edit permission'
        );
        
        // Assert: Sales should have customer permissions
        $this->assertContains(
            'customers.view',
            $salesPermissions,
            'SALES role should have customers.view permission'
        );
        
        // Assert: Sales should have order view permission
        $this->assertContains(
            'orders.view',
            $salesPermissions,
            'SALES role should have orders.view permission'
        );
        
        // Assert: Sales should NOT have content permissions
        $this->assertNotContains(
            'content.create',
            $salesPermissions,
            'SALES role should not have content.create permission'
        );
        $this->assertNotContains(
            'content.delete',
            $salesPermissions,
            'SALES role should not have content.delete permission'
        );
        
        // Assert: Sales should NOT have port management permissions
        $this->assertNotContains(
            'ports.create',
            $salesPermissions,
            'SALES role should not have ports.create permission'
        );
        $this->assertNotContains(
            'ports.delete',
            $salesPermissions,
            'SALES role should not have ports.delete permission'
        );
    }

    /**
     * Property: Content Editor role has appropriate permissions
     * 
     * Content Editor role should have content and media permissions,
     * but not user, customer, or system management permissions.
     * 
     * @test
     */
    public function contentEditorRoleHasAppropriatePermissions(): void
    {
        $editorPermissions = RoleService::getPermissions('CONTENT_EDITOR');
        
        // Assert: Content Editor should have content permissions
        $this->assertContains(
            'content.view',
            $editorPermissions,
            'CONTENT_EDITOR role should have content.view permission'
        );
        $this->assertContains(
            'content.create',
            $editorPermissions,
            'CONTENT_EDITOR role should have content.create permission'
        );
        $this->assertContains(
            'content.edit',
            $editorPermissions,
            'CONTENT_EDITOR role should have content.edit permission'
        );
        $this->assertContains(
            'content.publish',
            $editorPermissions,
            'CONTENT_EDITOR role should have content.publish permission'
        );
        
        // Assert: Content Editor should have media permissions
        $this->assertContains(
            'media.view',
            $editorPermissions,
            'CONTENT_EDITOR role should have media.view permission'
        );
        $this->assertContains(
            'media.upload',
            $editorPermissions,
            'CONTENT_EDITOR role should have media.upload permission'
        );
        
        // Assert: Content Editor should NOT have user management permissions
        $this->assertNotContains(
            'users.view',
            $editorPermissions,
            'CONTENT_EDITOR role should not have users.view permission'
        );
        $this->assertNotContains(
            'users.create',
            $editorPermissions,
            'CONTENT_EDITOR role should not have users.create permission'
        );
        
        // Assert: Content Editor should NOT have customer management permissions
        $this->assertNotContains(
            'customers.view',
            $editorPermissions,
            'CONTENT_EDITOR role should not have customers.view permission'
        );
        
        // Assert: Content Editor should NOT have settings permissions
        $this->assertNotContains(
            'settings.edit',
            $editorPermissions,
            'CONTENT_EDITOR role should not have settings.edit permission'
        );
    }

    /**
     * Property: Permission format is consistent
     * 
     * All permissions should follow the format "category.action".
     * 
     * @test
     */
    public function permissionFormatIsConsistent(): void
    {
        $allPermissions = RoleService::getAllPermissions();
        
        foreach ($allPermissions as $permission) {
            // Assert: Permission should be a string
            $this->assertIsString(
                $permission,
                'Permission should be a string'
            );
            
            // Assert: Permission should contain exactly one dot
            $this->assertEquals(
                1,
                substr_count($permission, '.'),
                "Permission '{$permission}' should contain exactly one dot"
            );
            
            // Assert: Permission should follow format "category.action"
            $this->assertMatchesRegularExpression(
                '/^[a-z_]+\.[a-z_]+$/',
                $permission,
                "Permission '{$permission}' should follow format 'category.action'"
            );
            
            // Assert: Category and action should not be empty
            list($category, $action) = explode('.', $permission);
            $this->assertNotEmpty(
                $category,
                "Permission category should not be empty"
            );
            $this->assertNotEmpty(
                $action,
                "Permission action should not be empty"
            );
        }
    }

    /**
     * Property: Role validation works correctly
     * 
     * @test
     */
    public function roleValidationWorksCorrectly(): void
    {
        // Valid roles
        $this->assertTrue(
            RoleService::isValidRole('ADMIN'),
            'ADMIN should be a valid role'
        );
        $this->assertTrue(
            RoleService::isValidRole('SUPPORT'),
            'SUPPORT should be a valid role'
        );
        $this->assertTrue(
            RoleService::isValidRole('SALES'),
            'SALES should be a valid role'
        );
        $this->assertTrue(
            RoleService::isValidRole('CONTENT_EDITOR'),
            'CONTENT_EDITOR should be a valid role'
        );
        
        // Invalid roles
        $this->assertFalse(
            RoleService::isValidRole('INVALID'),
            'INVALID should not be a valid role'
        );
        $this->assertFalse(
            RoleService::isValidRole('admin'),
            'admin (lowercase) should not be a valid role'
        );
        $this->assertFalse(
            RoleService::isValidRole(''),
            'Empty string should not be a valid role'
        );
        $this->assertFalse(
            RoleService::isValidRole('CUSTOMER'),
            'CUSTOMER should not be a valid admin role'
        );
    }

    /**
     * Property: userHasAnyPermission works correctly
     * 
     * @test
     */
    public function userHasAnyPermissionWorksCorrectly(): void
    {
        $this->forAll(
            Generator\elements(['ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR'])
        )
        ->then(function ($role) {
            // Create a test user with specific role
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            $this->cleanupUserByEmail($email);
            
            $user = $this->createUserWithRole($email, $role);
            
            if (!$user) {
                return; // Skip if user creation failed
            }
            
            // Get permissions for this role
            $rolePermissions = RoleService::getPermissions($role);
            
            if (empty($rolePermissions)) {
                $this->cleanupUserByEmail($email);
                return;
            }
            
            // Test with permissions user has
            $hasAny = RoleService::userHasAnyPermission($user, $rolePermissions);
            $this->assertTrue(
                $hasAny,
                "User with role {$role} should have at least one of their role permissions"
            );
            
            // Test with permissions user doesn't have
            $hasAny = RoleService::userHasAnyPermission($user, ['nonexistent.permission', 'another.fake']);
            $this->assertFalse(
                $hasAny,
                "User should not have nonexistent permissions"
            );
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: userHasAllPermissions works correctly
     * 
     * @test
     */
    public function userHasAllPermissionsWorksCorrectly(): void
    {
        $this->forAll(
            Generator\elements(['ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR'])
        )
        ->then(function ($role) {
            // Create a test user with specific role
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            $this->cleanupUserByEmail($email);
            
            $user = $this->createUserWithRole($email, $role);
            
            if (!$user) {
                return; // Skip if user creation failed
            }
            
            // Get permissions for this role
            $rolePermissions = RoleService::getPermissions($role);
            
            if (empty($rolePermissions)) {
                $this->cleanupUserByEmail($email);
                return;
            }
            
            // Test with all permissions user has
            $hasAll = RoleService::userHasAllPermissions($user, $rolePermissions);
            $this->assertTrue(
                $hasAll,
                "User with role {$role} should have all their role permissions"
            );
            
            // Test with mix of permissions (some user has, some doesn't)
            $mixedPermissions = array_merge(
                array_slice($rolePermissions, 0, 1),
                ['nonexistent.permission']
            );
            $hasAll = RoleService::userHasAllPermissions($user, $mixedPermissions);
            $this->assertFalse(
                $hasAll,
                "User should not have all permissions when some are missing"
            );
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Helper: Create user with specific role
     */
    private function createUserWithRole(
        string $email,
        string $role,
        string $password = 'password123',
        string $name = 'Test User'
    ): ?array {
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => $password,
            'name' => $name,
            'phone' => '1234567890'
        ]);
        
        if (!$registerResult['success']) {
            return null;
        }
        
        // Update user role
        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $this->userModel->update($user['id'], ['role' => $role]);
            $user = $this->userModel->findByEmail($email);
        }
        
        return $user ?: null;
    }

    /**
     * Helper: Clean up user by email
     */
    private function cleanupUserByEmail(string $email): void
    {
        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $this->userModel->delete($user['id']);
        }
    }
}

