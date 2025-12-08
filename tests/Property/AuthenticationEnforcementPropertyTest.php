<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Middleware\AuthMiddleware;
use Karyalay\Services\AuthService;
use Karyalay\Models\User;

/**
 * Property-based tests for authentication enforcement
 * 
 * Feature: karyalay-portal-system, Property 42: Authentication Enforcement
 * Validates: Requirements 13.2
 */
class AuthenticationEnforcementPropertyTest extends TestCase
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
     * Property 42: Authentication Enforcement
     * 
     * For any customer portal route, when accessed without authentication, 
     * the request should be redirected to the login page.
     * 
     * Validates: Requirements 13.2
     * 
     * @test
     * @runInSeparateProcess
     */
    public function customerPortalRoutesRequireAuthentication(): void
    {
        $this->forAll(
            Generator\elements([null, '', 'invalid-token', 'expired-token'])
        )
        ->then(function ($invalidToken) {
            // Test that invalid tokens are rejected by validateSession
            $result = $this->authMiddleware->validateSession($invalidToken ?: 'invalid');
            
            // Assert: Should return false (indicating authentication failure)
            $this->assertFalse(
                $result,
                'Customer portal access without valid authentication should be rejected'
            );
        });
    }

    /**
     * Property: Valid session token grants access
     * 
     * For any valid session token, when accessing customer portal routes,
     * access should be granted and user data returned.
     * 
     * @test
     */
    public function validSessionTokenGrantsAccess(): void
    {
        $this->forAll(
            Generator\string(),
            Generator\string()
        )
        ->when(function ($password, $name) {
            return strlen($password) >= 8 && strlen($name) >= 1;
        })
        ->then(function ($password, $name) {
            // Create a test user
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            $this->cleanupUserByEmail($email);
            
            $registerResult = $this->authService->register([
                'email' => $email,
                'password' => $password,
                'name' => $name,
                'phone' => '1234567890'
            ]);
            
            if (!$registerResult['success']) {
                $this->cleanupUserByEmail($email);
                return; // Skip this iteration if registration failed
            }
            
            // Login to get valid session token
            $loginResult = $this->authService->login($email, $password);
            
            $this->assertTrue(
                $loginResult['success'],
                'Login should succeed with valid credentials'
            );
            
            $sessionToken = $loginResult['session']['token'];
            
            // Validate session using middleware
            $user = $this->authMiddleware->validateSession($sessionToken);
            
            // Assert: Valid token returns user data
            $this->assertIsArray(
                $user,
                'Valid session token should return user data'
            );
            $this->assertEquals($email, $user['email']);
            $this->assertEquals($name, $user['name']);
            
            // Cleanup
            $this->authService->logout($sessionToken);
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Expired session tokens are rejected
     * 
     * @test
     */
    public function expiredSessionTokensAreRejected(): void
    {
        // Create a test user
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $this->cleanupUserByEmail($email);
        
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => 'password123',
            'name' => 'Test User',
            'phone' => '1234567890'
        ]);
        
        if (!$registerResult['success']) {
            $this->cleanupUserByEmail($email);
            $this->markTestSkipped('Could not create test user');
            return;
        }
        
        // Login to get session token
        $loginResult = $this->authService->login($email, 'password123');
        $sessionToken = $loginResult['session']['token'];
        
        // Logout to invalidate session
        $this->authService->logout($sessionToken);
        
        // Try to validate expired/logged-out session
        $user = $this->authMiddleware->validateSession($sessionToken);
        
        // Assert: Expired token returns false
        $this->assertFalse(
            $user,
            'Expired or logged-out session token should be rejected'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Malformed tokens are rejected
     * 
     * @test
     */
    public function malformedTokensAreRejected(): void
    {
        $this->forAll(
            Generator\elements([
                'not-a-valid-token',
                '12345',
                'abc',
                'token-with-special-chars-!@#$',
                str_repeat('a', 100),
                ''
            ])
        )
        ->then(function ($malformedToken) {
            $user = $this->authMiddleware->validateSession($malformedToken);
            
            // Assert: Malformed token returns false
            $this->assertFalse(
                $user,
                'Malformed token should be rejected: ' . $malformedToken
            );
        });
    }

    /**
     * Property: Session validation is consistent
     * 
     * For any valid session, multiple validation attempts should return
     * the same user data (idempotent).
     * 
     * @test
     */
    public function sessionValidationIsIdempotent(): void
    {
        // Create a test user
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $this->cleanupUserByEmail($email);
        
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => 'password123',
            'name' => 'Test User',
            'phone' => '1234567890'
        ]);
        
        if (!$registerResult['success']) {
            $this->cleanupUserByEmail($email);
            $this->markTestSkipped('Could not create test user');
            return;
        }
        
        // Login to get session token
        $loginResult = $this->authService->login($email, 'password123');
        $sessionToken = $loginResult['session']['token'];
        
        // Validate session multiple times
        $user1 = $this->authMiddleware->validateSession($sessionToken);
        $user2 = $this->authMiddleware->validateSession($sessionToken);
        $user3 = $this->authMiddleware->validateSession($sessionToken);
        
        // Assert: All validations return same user data
        $this->assertEquals(
            $user1,
            $user2,
            'Multiple validations should return same user data'
        );
        $this->assertEquals(
            $user2,
            $user3,
            'Multiple validations should return same user data'
        );
        
        // Cleanup
        $this->authService->logout($sessionToken);
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Authentication check does not leak sensitive data
     * 
     * @test
     */
    public function authenticationCheckDoesNotLeakSensitiveData(): void
    {
        // Create a test user
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $this->cleanupUserByEmail($email);
        
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => 'password123',
            'name' => 'Test User',
            'phone' => '1234567890'
        ]);
        
        if (!$registerResult['success']) {
            $this->cleanupUserByEmail($email);
            $this->markTestSkipped('Could not create test user');
            return;
        }
        
        // Login to get session token
        $loginResult = $this->authService->login($email, 'password123');
        $sessionToken = $loginResult['session']['token'];
        
        // Validate session
        $user = $this->authMiddleware->validateSession($sessionToken);
        
        // Assert: User data does not include password_hash
        $this->assertIsArray($user);
        $this->assertArrayNotHasKey(
            'password_hash',
            $user,
            'Validated user data should not include password_hash'
        );
        
        // Cleanup
        $this->authService->logout($sessionToken);
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
