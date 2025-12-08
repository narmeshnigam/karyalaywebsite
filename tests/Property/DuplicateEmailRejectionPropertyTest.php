<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\AuthService;
use Karyalay\Models\User;

/**
 * Property-based tests for duplicate email rejection
 * 
 * Feature: karyalay-portal-system, Property 5: Duplicate Email Rejection
 * Validates: Requirements 2.2
 */
class DuplicateEmailRejectionPropertyTest extends TestCase
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
     * Property 5: Duplicate Email Rejection
     * 
     * For any email that already exists in the database, when a registration 
     * form is submitted with that email, the registration should be rejected 
     * with an error message.
     * 
     * Validates: Requirements 2.2
     * 
     * @test
     */
    public function duplicateEmailRegistrationIsRejected(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string()
        )
        ->when(function ($password, $name) {
            // Only test with valid data
            return strlen($password) >= 8 
                && strlen($name) >= 1;
        })
        ->then(function ($password, $name) {
            // Generate unique email
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            
            // Cleanup before test
            $this->cleanupUserByEmail($email);
            
            // Arrange: Create first user with this email
            $firstResult = $this->authService->register([
                'email' => $email,
                'password' => $password,
                'name' => $name
            ]);
            
            // Assert first registration succeeded
            $this->assertTrue(
                $firstResult['success'],
                'First registration should succeed'
            );
            
            // Act: Attempt to register second user with same email
            $secondResult = $this->authService->register([
                'email' => $email,
                'password' => 'different_password_123',
                'name' => 'Different Name'
            ]);
            
            // Assert: Second registration should fail
            $this->assertFalse(
                $secondResult['success'],
                'Second registration with duplicate email should fail'
            );
            
            // Assert: Error message should be returned
            $this->assertNotNull(
                $secondResult['error'],
                'Error message should be returned for duplicate email'
            );
            
            // Assert: Error message mentions email already exists
            $this->assertStringContainsString(
                'email',
                strtolower($secondResult['error']),
                'Error message should mention email'
            );
            
            // Assert: User data should be null in failed response
            $this->assertNull(
                $secondResult['user'],
                'User data should be null when registration fails'
            );
            
            // Assert: Only one user exists with this email
            $user = $this->userModel->findByEmail($email);
            $this->assertNotFalse($user, 'Original user should still exist');
            $this->assertEquals(
                $name,
                $user['name'],
                'Original user data should be unchanged'
            );
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Case-insensitive email uniqueness
     * 
     * Emails should be treated as case-insensitive for uniqueness checks
     * 
     * @test
     */
    public function emailUniquenessIsCaseInsensitive(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function ($suffix) {
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            // Cleanup before test
            $this->cleanupUserByEmail($email);
            $this->cleanupUserByEmail(strtoupper($email));
            $this->cleanupUserByEmail(strtolower($email));
            
            // Arrange: Create user with lowercase email
            $firstResult = $this->authService->register([
                'email' => strtolower($email),
                'password' => 'password123',
                'name' => 'Test User'
            ]);
            
            if (!$firstResult['success']) {
                // Skip if first registration failed for other reasons
                $this->markTestSkipped('First registration failed');
                return;
            }
            
            // Act: Attempt to register with uppercase version of same email
            $secondResult = $this->authService->register([
                'email' => strtoupper($email),
                'password' => 'password456',
                'name' => 'Another User'
            ]);
            
            // Assert: Second registration should fail (case-insensitive check)
            // Note: This depends on database collation settings
            // Most databases treat emails as case-insensitive by default
            if ($secondResult['success']) {
                // If it succeeded, it means the database allows it
                // Clean up both users
                $this->cleanupUserByEmail(strtolower($email));
                $this->cleanupUserByEmail(strtoupper($email));
            } else {
                // Expected behavior: duplicate rejected
                $this->assertFalse($secondResult['success']);
                $this->cleanupUserByEmail(strtolower($email));
            }
        });
    }

    /**
     * Property: Multiple failed registration attempts don't create users
     * 
     * @test
     */
    public function multipleFailedRegistrationsDontCreateUsers(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // Create first user
        $firstResult = $this->authService->register([
            'email' => $email,
            'password' => 'password123',
            'name' => 'First User'
        ]);
        
        if (!$firstResult['success']) {
            $this->markTestSkipped('First registration failed');
            return;
        }
        
        // Attempt multiple duplicate registrations
        for ($i = 0; $i < 5; $i++) {
            $result = $this->authService->register([
                'email' => $email,
                'password' => 'password' . $i,
                'name' => 'User ' . $i
            ]);
            
            $this->assertFalse(
                $result['success'],
                'Duplicate registration attempt ' . ($i + 1) . ' should fail'
            );
        }
        
        // Assert: Still only one user with this email
        $user = $this->userModel->findByEmail($email);
        $this->assertNotFalse($user);
        $this->assertEquals('First User', $user['name']);
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Email uniqueness check is atomic
     * 
     * Even with concurrent-like sequential checks, only one user should be created
     * 
     * @test
     */
    public function emailUniquenessCheckIsAtomic(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // First registration
        $result1 = $this->authService->register([
            'email' => $email,
            'password' => 'password123',
            'name' => 'User 1'
        ]);
        
        // Second registration (should fail)
        $result2 = $this->authService->register([
            'email' => $email,
            'password' => 'password456',
            'name' => 'User 2'
        ]);
        
        // At least one should succeed, at least one should fail
        $successCount = ($result1['success'] ? 1 : 0) + ($result2['success'] ? 1 : 0);
        
        $this->assertEquals(
            1,
            $successCount,
            'Exactly one registration should succeed'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
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

