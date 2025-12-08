<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Middleware\AuthMiddleware;
use Karyalay\Services\AuthService;
use Karyalay\Models\User;

/**
 * Property-based tests for authorization enforcement
 * 
 * Feature: karyalay-portal-system, Property 43: Authorization Enforcement
 * Validates: Requirements 13.3
 */
class AuthorizationEnforcementPropertyTest extends TestCase
{
    use TestTrait;

    private AuthMiddleware $authMiddleware;
    private AuthService $authService;
    private User $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authMiddleware = new AuthMiddleware();
        $this->authService = new AuthService();
        $this->userModel = new User();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 43: Authorization Enforcement
     * 
     * For any admin route, when accessed by a user without admin role, 
     * access should be denied with an unauthorized error.
     * 
     * Validates: Requirements 13.3
     * 
     * @test
     */
    public function adminRoutesRequireAdminRole(): void
    {
        $this->forAll(
            Generator\elements(['CUSTOMER', 'SUPPORT', 'CONTENT_EDITOR'])
        )
        ->then(function ($nonAdminRole) {
            // Create a test user with non-admin role
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            $this->cleanupUserByEmail($email);
            
            $user = $this->createUserWithRole($email, $nonAdminRole);
            
            if (!$user) {
                return; // Skip if user creation failed
            }
            
            // Test hasRole method
            $hasAdminRole = $this->authMiddleware->hasRole($user, 'ADMIN');
            
            // Assert: Non-admin users should not have admin role
            $this->assertFalse(
                $hasAdminRole,
                "User with role {$nonAdminRole} should not have ADMIN role"
            );
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Admin users have access to admin routes
     * 
     * For any user with ADMIN role, when accessing admin routes,
     * access should be granted.
     * 
     * @test
     */
    public function adminUsersHaveAccessToAdminRoutes(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string()
        )
        ->when(function ($password, $name) {
            return strlen($password) >= 8 && strlen($name) >= 1;
        })
        ->then(function ($password, $name) {
            // Create a test admin user
            $email = 'admin_' . bin2hex(random_bytes(8)) . '@example.com';
            $this->cleanupUserByEmail($email);
            
            $user = $this->createUserWithRole($email, 'ADMIN', $password, $name);
            
            if (!$user) {
                return; // Skip if user creation failed
            }
            
            // Test hasRole method
            $hasAdminRole = $this->authMiddleware->hasRole($user, 'ADMIN');
            
            // Assert: Admin users should have admin role
            $this->assertTrue(
                $hasAdminRole,
                'User with ADMIN role should have admin access'
            );
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Role checking is case-sensitive
     * 
     * @test
     */
    public function roleCheckingIsCaseSensitive(): void
    {
        // Create a test admin user
        $email = 'admin_' . bin2hex(random_bytes(8)) . '@example.com';
        $this->cleanupUserByEmail($email);
        
        $user = $this->createUserWithRole($email, 'ADMIN');
        
        if (!$user) {
            $this->markTestSkipped('Could not create test user');
            return;
        }
        
        // Test with correct case
        $this->assertTrue(
            $this->authMiddleware->hasRole($user, 'ADMIN'),
            'Role check should succeed with correct case'
        );
        
        // Test with incorrect case
        $this->assertFalse(
            $this->authMiddleware->hasRole($user, 'admin'),
            'Role check should fail with incorrect case'
        );
        
        $this->assertFalse(
            $this->authMiddleware->hasRole($user, 'Admin'),
            'Role check should fail with incorrect case'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Multiple role checking works correctly
     * 
     * @test
     */
    public function multipleRoleCheckingWorksCorrectly(): void
    {
        $this->forAll(
            Generator\elements(['ADMIN', 'SUPPORT', 'CONTENT_EDITOR', 'CUSTOMER'])
        )
        ->then(function ($userRole) {
            // Create a test user with specific role
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            $this->cleanupUserByEmail($email);
            
            $user = $this->createUserWithRole($email, $userRole);
            
            if (!$user) {
                return; // Skip if user creation failed
            }
            
            // Test with array of roles that includes user's role
            $hasRole = $this->authMiddleware->hasRole($user, ['ADMIN', 'SUPPORT', 'CONTENT_EDITOR', 'CUSTOMER']);
            $this->assertTrue(
                $hasRole,
                "User with role {$userRole} should match when included in role array"
            );
            
            // Test with array of roles that doesn't include user's role
            $otherRoles = array_diff(['ADMIN', 'SUPPORT', 'CONTENT_EDITOR', 'CUSTOMER'], [$userRole]);
            $hasRole = $this->authMiddleware->hasRole($user, array_values($otherRoles));
            $this->assertFalse(
                $hasRole,
                "User with role {$userRole} should not match when not included in role array"
            );
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Support staff can access support routes but not all admin routes
     * 
     * @test
     */
    public function supportStaffHasLimitedAccess(): void
    {
        // Create a test support user
        $email = 'support_' . bin2hex(random_bytes(8)) . '@example.com';
        $this->cleanupUserByEmail($email);
        
        $user = $this->createUserWithRole($email, 'SUPPORT');
        
        if (!$user) {
            $this->markTestSkipped('Could not create test user');
            return;
        }
        
        // Support staff should have support role
        $this->assertTrue(
            $this->authMiddleware->hasRole($user, 'SUPPORT'),
            'Support user should have SUPPORT role'
        );
        
        // Support staff should have access to support routes (ADMIN or SUPPORT)
        $this->assertTrue(
            $this->authMiddleware->hasRole($user, ['ADMIN', 'SUPPORT']),
            'Support user should have access to support routes'
        );
        
        // Support staff should NOT have admin-only access
        $this->assertFalse(
            $this->authMiddleware->hasRole($user, 'ADMIN'),
            'Support user should not have ADMIN role'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Content editors can access content routes but not admin routes
     * 
     * @test
     */
    public function contentEditorsHaveLimitedAccess(): void
    {
        // Create a test content editor user
        $email = 'editor_' . bin2hex(random_bytes(8)) . '@example.com';
        $this->cleanupUserByEmail($email);
        
        $user = $this->createUserWithRole($email, 'CONTENT_EDITOR');
        
        if (!$user) {
            $this->markTestSkipped('Could not create test user');
            return;
        }
        
        // Content editor should have content editor role
        $this->assertTrue(
            $this->authMiddleware->hasRole($user, 'CONTENT_EDITOR'),
            'Content editor should have CONTENT_EDITOR role'
        );
        
        // Content editor should have access to content routes (ADMIN or CONTENT_EDITOR)
        $this->assertTrue(
            $this->authMiddleware->hasRole($user, ['ADMIN', 'CONTENT_EDITOR']),
            'Content editor should have access to content routes'
        );
        
        // Content editor should NOT have admin-only access
        $this->assertFalse(
            $this->authMiddleware->hasRole($user, 'ADMIN'),
            'Content editor should not have ADMIN role'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Customers can access customer portal but not admin routes
     * 
     * @test
     */
    public function customersHaveLimitedAccess(): void
    {
        // Create a test customer user
        $email = 'customer_' . bin2hex(random_bytes(8)) . '@example.com';
        $this->cleanupUserByEmail($email);
        
        $user = $this->createUserWithRole($email, 'CUSTOMER');
        
        if (!$user) {
            $this->markTestSkipped('Could not create test user');
            return;
        }
        
        // Customer should have customer role
        $this->assertTrue(
            $this->authMiddleware->hasRole($user, 'CUSTOMER'),
            'Customer should have CUSTOMER role'
        );
        
        // Customer should have access to customer portal (CUSTOMER or ADMIN)
        $this->assertTrue(
            $this->authMiddleware->hasRole($user, ['CUSTOMER', 'ADMIN']),
            'Customer should have access to customer portal'
        );
        
        // Customer should NOT have admin access
        $this->assertFalse(
            $this->authMiddleware->hasRole($user, 'ADMIN'),
            'Customer should not have ADMIN role'
        );
        
        // Customer should NOT have support access
        $this->assertFalse(
            $this->authMiddleware->hasRole($user, 'SUPPORT'),
            'Customer should not have SUPPORT role'
        );
        
        // Customer should NOT have content editor access
        $this->assertFalse(
            $this->authMiddleware->hasRole($user, 'CONTENT_EDITOR'),
            'Customer should not have CONTENT_EDITOR role'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Role enforcement is consistent across multiple checks
     * 
     * @test
     */
    public function roleEnforcementIsConsistent(): void
    {
        // Create a test user
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $this->cleanupUserByEmail($email);
        
        $user = $this->createUserWithRole($email, 'CUSTOMER');
        
        if (!$user) {
            $this->markTestSkipped('Could not create test user');
            return;
        }
        
        // Check role multiple times
        $check1 = $this->authMiddleware->hasRole($user, 'CUSTOMER');
        $check2 = $this->authMiddleware->hasRole($user, 'CUSTOMER');
        $check3 = $this->authMiddleware->hasRole($user, 'CUSTOMER');
        
        // Assert: All checks should return same result
        $this->assertEquals(
            $check1,
            $check2,
            'Multiple role checks should return consistent results'
        );
        $this->assertEquals(
            $check2,
            $check3,
            'Multiple role checks should return consistent results'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
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
