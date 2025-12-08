<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\AuthService;
use Karyalay\Models\User;
use Karyalay\Models\Session;

/**
 * Property-based tests for authentication round trip
 * 
 * Feature: karyalay-portal-system, Property 6: Authentication Round Trip
 * Validates: Requirements 2.3
 */
class AuthenticationRoundTripPropertyTest extends TestCase
{
    use TestTrait;

    private AuthService $authService;
    private User $userModel;
    private Session $sessionModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
        $this->userModel = new User();
        $this->sessionModel = new Session();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 6: Authentication Round Trip
     * 
     * For any valid user account, when correct credentials are submitted, 
     * an authenticated session should be created, and when those credentials 
     * are used to access protected routes, access should be granted.
     * 
     * Validates: Requirements 2.3
     * 
     * @test
     */
    public function authenticationRoundTripCreatesValidSession(): void
    {
        $this->forAll(
            Generator\choose(8, 50),  // password length
            Generator\choose(1, 50),  // name length
            Generator\choose(10, 20)  // phone length
        )
        ->then(function ($passwordLen, $nameLen, $phoneLen) {
            // Generate test data
            $password = str_repeat('a', $passwordLen);
            $name = 'User' . str_repeat('X', max(0, $nameLen - 4));
            $phone = str_repeat('1', $phoneLen);
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            
            // Cleanup before test
            $this->cleanupUserByEmail($email);
            
            // Arrange: Register a user
            $registerResult = $this->authService->register([
                'email' => $email,
                'password' => $password,
                'name' => $name,
                'phone' => $phone
            ]);
            
            if (!$registerResult['success']) {
                $this->markTestSkipped('User registration failed: ' . ($registerResult['error'] ?? 'unknown'));
                return;
            }
            
            $registeredUser = $registerResult['user'];
            
            // Act: Login with correct credentials
            $loginResult = $this->authService->login($email, $password);
            
            // Assert: Login succeeded
            $this->assertTrue(
                $loginResult['success'],
                'Login should succeed with correct credentials'
            );
            $this->assertNull(
                $loginResult['error'],
                'No error should be returned on successful login'
            );
            
            // Assert: User data is returned
            $this->assertNotNull(
                $loginResult['user'],
                'User data should be returned on successful login'
            );
            $this->assertEquals(
                $email,
                $loginResult['user']['email'],
                'Returned user email should match'
            );
            $this->assertEquals(
                $name,
                $loginResult['user']['name'],
                'Returned user name should match'
            );
            
            // Assert: User data does not include password_hash
            $this->assertArrayNotHasKey(
                'password_hash',
                $loginResult['user'],
                'User data should not include password_hash'
            );
            
            // Assert: Session is created
            $this->assertNotNull(
                $loginResult['session'],
                'Session should be created on successful login'
            );
            $this->assertArrayHasKey('id', $loginResult['session']);
            $this->assertArrayHasKey('token', $loginResult['session']);
            $this->assertArrayHasKey('user_id', $loginResult['session']);
            $this->assertArrayHasKey('expires_at', $loginResult['session']);
            
            // Assert: Session is associated with correct user
            $this->assertEquals(
                $registeredUser['id'],
                $loginResult['session']['user_id'],
                'Session should be associated with the correct user'
            );
            
            // Assert: Session token is not empty and has reasonable length
            $sessionToken = $loginResult['session']['token'];
            $this->assertNotEmpty($sessionToken);
            $this->assertGreaterThan(
                32,
                strlen($sessionToken),
                'Session token should be sufficiently long for security'
            );
            
            // Act: Validate the session token (simulating protected route access)
            $validationResult = $this->authService->validateSession($sessionToken);
            
            // Assert: Session validation succeeds
            $this->assertNotFalse(
                $validationResult,
                'Session token should be valid immediately after login'
            );
            $this->assertIsArray($validationResult);
            $this->assertArrayHasKey('session', $validationResult);
            $this->assertArrayHasKey('user', $validationResult);
            
            // Assert: Validated session matches login session
            $this->assertEquals(
                $loginResult['session']['id'],
                $validationResult['session']['id'],
                'Validated session should match login session'
            );
            $this->assertEquals(
                $sessionToken,
                $validationResult['session']['token'],
                'Validated session token should match'
            );
            
            // Assert: Validated user matches registered user
            $this->assertEquals(
                $registeredUser['id'],
                $validationResult['user']['id'],
                'Validated user should match registered user'
            );
            $this->assertEquals(
                $email,
                $validationResult['user']['email'],
                'Validated user email should match'
            );
            
            // Assert: Validated user data does not include password_hash
            $this->assertArrayNotHasKey(
                'password_hash',
                $validationResult['user'],
                'Validated user data should not include password_hash'
            );
            
            // Assert: Session exists in database
            $dbSession = $this->sessionModel->findByToken($sessionToken);
            $this->assertNotFalse(
                $dbSession,
                'Session should exist in database'
            );
            $this->assertEquals(
                $registeredUser['id'],
                $dbSession['user_id'],
                'Database session should be associated with correct user'
            );
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Multiple logins create multiple sessions
     * 
     * @test
     */
    public function multipleLoginsCreateMultipleSessions(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $password = 'password123';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // Arrange: Register user
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => $password,
            'name' => 'Test User'
        ]);
        
        if (!$registerResult['success']) {
            $this->markTestSkipped('User registration failed');
            return;
        }
        
        $userId = $registerResult['user']['id'];
        
        // Act: Login multiple times
        $login1 = $this->authService->login($email, $password);
        $login2 = $this->authService->login($email, $password);
        $login3 = $this->authService->login($email, $password);
        
        // Assert: All logins succeeded
        $this->assertTrue($login1['success']);
        $this->assertTrue($login2['success']);
        $this->assertTrue($login3['success']);
        
        // Assert: Different session tokens
        $token1 = $login1['session']['token'];
        $token2 = $login2['session']['token'];
        $token3 = $login3['session']['token'];
        
        $this->assertNotEquals($token1, $token2);
        $this->assertNotEquals($token2, $token3);
        $this->assertNotEquals($token1, $token3);
        
        // Assert: All sessions are valid
        $this->assertNotFalse($this->authService->validateSession($token1));
        $this->assertNotFalse($this->authService->validateSession($token2));
        $this->assertNotFalse($this->authService->validateSession($token3));
        
        // Assert: All sessions exist in database
        $sessions = $this->sessionModel->findByUserId($userId);
        $this->assertGreaterThanOrEqual(3, count($sessions));
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Session validation fails for non-existent tokens
     * 
     * @test
     */
    public function sessionValidationFailsForNonExistentTokens(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function ($randomString) {
            // Generate a random token that doesn't exist
            $fakeToken = 'fake_token_' . bin2hex(random_bytes(32)) . $randomString;
            
            // Act: Try to validate non-existent token
            $result = $this->authService->validateSession($fakeToken);
            
            // Assert: Validation should fail
            $this->assertFalse(
                $result,
                'Session validation should fail for non-existent token'
            );
        });
    }

    /**
     * Property: Logout invalidates session
     * 
     * @test
     */
    public function logoutInvalidatesSession(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $password = 'password123';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // Arrange: Register and login
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => $password,
            'name' => 'Test User'
        ]);
        
        if (!$registerResult['success']) {
            $this->markTestSkipped('User registration failed');
            return;
        }
        
        $loginResult = $this->authService->login($email, $password);
        $sessionToken = $loginResult['session']['token'];
        
        // Assert: Session is valid before logout
        $this->assertNotFalse($this->authService->validateSession($sessionToken));
        
        // Act: Logout
        $logoutResult = $this->authService->logout($sessionToken);
        
        // Assert: Logout succeeded
        $this->assertTrue($logoutResult, 'Logout should succeed');
        
        // Assert: Session is no longer valid after logout
        $this->assertFalse(
            $this->authService->validateSession($sessionToken),
            'Session should be invalid after logout'
        );
        
        // Assert: Session does not exist in database
        $dbSession = $this->sessionModel->findByToken($sessionToken);
        $this->assertFalse(
            $dbSession,
            'Session should not exist in database after logout'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Session expiration is enforced
     * 
     * Note: This test creates a session with 0 hours expiration to simulate expiration
     * 
     * @test
     */
    public function expiredSessionsAreInvalid(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $password = 'password123';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // Arrange: Register user
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => $password,
            'name' => 'Test User'
        ]);
        
        if (!$registerResult['success']) {
            $this->markTestSkipped('User registration failed');
            return;
        }
        
        $userId = $registerResult['user']['id'];
        
        // Create a session that expires immediately (0 hours)
        $expiredSession = $this->sessionModel->create($userId, 0);
        
        // Wait a moment to ensure expiration
        sleep(1);
        
        // Act: Try to validate expired session
        $result = $this->authService->validateSession($expiredSession['token']);
        
        // Assert: Validation should fail for expired session
        $this->assertFalse(
            $result,
            'Expired session should not validate'
        );
        
        // Assert: Expired session should be deleted from database
        $dbSession = $this->sessionModel->findByToken($expiredSession['token']);
        $this->assertFalse(
            $dbSession,
            'Expired session should be deleted from database after validation attempt'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Session contains user role information
     * 
     * @test
     */
    public function sessionValidationIncludesUserRole(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $password = 'password123';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // Arrange: Register and login
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => $password,
            'name' => 'Test User'
        ]);
        
        if (!$registerResult['success']) {
            $this->markTestSkipped('User registration failed');
            return;
        }
        
        $loginResult = $this->authService->login($email, $password);
        $sessionToken = $loginResult['session']['token'];
        
        // Act: Validate session
        $validationResult = $this->authService->validateSession($sessionToken);
        
        // Assert: User role is included
        $this->assertArrayHasKey('role', $validationResult['user']);
        $this->assertEquals(
            'CUSTOMER',
            $validationResult['user']['role'],
            'Default user role should be CUSTOMER'
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
            // Delete sessions first
            $this->sessionModel->deleteByUserId($user['id']);
            // Delete user
            $this->userModel->delete($user['id']);
        }
    }
}
