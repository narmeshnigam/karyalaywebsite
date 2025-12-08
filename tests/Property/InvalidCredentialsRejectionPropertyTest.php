<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\AuthService;
use Karyalay\Models\User;
use Karyalay\Models\Session;

/**
 * Property-based tests for invalid credentials rejection
 * 
 * Feature: karyalay-portal-system, Property 7: Invalid Credentials Rejection
 * Validates: Requirements 2.4
 */
class InvalidCredentialsRejectionPropertyTest extends TestCase
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
     * Property 7: Invalid Credentials Rejection
     * 
     * For any invalid credential combination (wrong email or wrong password), 
     * when the login form is submitted, the login attempt should be rejected 
     * with an error message.
     * 
     * Validates: Requirements 2.4
     * 
     * @test
     */
    public function invalidCredentialsAreRejected(): void
    {
        $this->forAll(
            Generator\choose(8, 50),  // password length
            Generator\choose(8, 50)   // wrong password length
        )
        ->when(function ($passwordLen, $wrongPasswordLen) {
            // Ensure passwords are different
            return $passwordLen !== $wrongPasswordLen;
        })
        ->then(function ($passwordLen, $wrongPasswordLen) {
            // Generate test data
            $correctPassword = str_repeat('a', $passwordLen);
            $wrongPassword = str_repeat('b', $wrongPasswordLen);
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            
            // Cleanup before test
            $this->cleanupUserByEmail($email);
            
            // Arrange: Register a user with correct password
            $registerResult = $this->authService->register([
                'email' => $email,
                'password' => $correctPassword,
                'name' => 'Test User'
            ]);
            
            if (!$registerResult['success']) {
                $this->markTestSkipped('User registration failed: ' . ($registerResult['error'] ?? 'unknown'));
                return;
            }
            
            // Act: Attempt login with wrong password
            $loginResult = $this->authService->login($email, $wrongPassword);
            
            // Assert: Login should fail
            $this->assertFalse(
                $loginResult['success'],
                'Login should fail with incorrect password'
            );
            
            // Assert: Error message is returned
            $this->assertNotNull(
                $loginResult['error'],
                'Error message should be returned for invalid credentials'
            );
            
            // Assert: Error message is generic (doesn't reveal which part is wrong)
            $errorMessage = strtolower($loginResult['error']);
            $this->assertTrue(
                str_contains($errorMessage, 'invalid') || str_contains($errorMessage, 'incorrect'),
                'Error message should indicate invalid credentials'
            );
            
            // Assert: User data is null
            $this->assertNull(
                $loginResult['user'],
                'User data should be null when login fails'
            );
            
            // Assert: Session is null
            $this->assertNull(
                $loginResult['session'],
                'Session should be null when login fails'
            );
            
            // Assert: No session was created in database
            $sessions = $this->sessionModel->findByUserId($registerResult['user']['id']);
            $this->assertEmpty(
                $sessions,
                'No session should be created for failed login'
            );
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Login with non-existent email is rejected
     * 
     * @test
     */
    public function loginWithNonExistentEmailIsRejected(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function ($suffix) {
            // Generate an email that doesn't exist
            $email = 'nonexistent_' . bin2hex(random_bytes(8)) . '@example.com';
            $password = 'password123';
            
            // Ensure email doesn't exist
            $this->cleanupUserByEmail($email);
            
            // Act: Attempt login with non-existent email
            $loginResult = $this->authService->login($email, $password);
            
            // Assert: Login should fail
            $this->assertFalse(
                $loginResult['success'],
                'Login should fail with non-existent email'
            );
            
            // Assert: Error message is returned
            $this->assertNotNull(
                $loginResult['error'],
                'Error message should be returned for non-existent email'
            );
            
            // Assert: Error message is generic (doesn't reveal email doesn't exist)
            $errorMessage = strtolower($loginResult['error']);
            $this->assertTrue(
                str_contains($errorMessage, 'invalid') || str_contains($errorMessage, 'incorrect'),
                'Error message should be generic for security'
            );
            
            // Assert: User data is null
            $this->assertNull($loginResult['user']);
            
            // Assert: Session is null
            $this->assertNull($loginResult['session']);
        });
    }

    /**
     * Property: Login with empty password is rejected
     * 
     * @test
     */
    public function loginWithEmptyPasswordIsRejected(): void
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
        
        // Act: Attempt login with empty password
        $loginResult = $this->authService->login($email, '');
        
        // Assert: Login should fail
        $this->assertFalse(
            $loginResult['success'],
            'Login should fail with empty password'
        );
        
        // Assert: Error message is returned
        $this->assertNotNull($loginResult['error']);
        
        // Assert: No session created
        $this->assertNull($loginResult['session']);
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Login with empty email is rejected
     * 
     * @test
     */
    public function loginWithEmptyEmailIsRejected(): void
    {
        // Act: Attempt login with empty email
        $loginResult = $this->authService->login('', 'password123');
        
        // Assert: Login should fail
        $this->assertFalse(
            $loginResult['success'],
            'Login should fail with empty email'
        );
        
        // Assert: Error message is returned
        $this->assertNotNull($loginResult['error']);
        
        // Assert: No session created
        $this->assertNull($loginResult['session']);
    }

    /**
     * Property: Multiple failed login attempts don't create sessions
     * 
     * @test
     */
    public function multipleFailedLoginsDontCreateSessions(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $correctPassword = 'password123';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // Arrange: Register user
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => $correctPassword,
            'name' => 'Test User'
        ]);
        
        if (!$registerResult['success']) {
            $this->markTestSkipped('User registration failed');
            return;
        }
        
        $userId = $registerResult['user']['id'];
        
        // Act: Attempt multiple failed logins
        for ($i = 0; $i < 10; $i++) {
            $wrongPassword = 'wrong_password_' . $i;
            $loginResult = $this->authService->login($email, $wrongPassword);
            
            $this->assertFalse(
                $loginResult['success'],
                'Login attempt ' . ($i + 1) . ' should fail'
            );
        }
        
        // Assert: No sessions were created
        $sessions = $this->sessionModel->findByUserId($userId);
        $this->assertEmpty(
            $sessions,
            'No sessions should be created from failed login attempts'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Case sensitivity in password is enforced
     * 
     * @test
     */
    public function passwordCaseSensitivityIsEnforced(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $password = 'Password123';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // Arrange: Register user with mixed case password
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => $password,
            'name' => 'Test User'
        ]);
        
        if (!$registerResult['success']) {
            $this->markTestSkipped('User registration failed');
            return;
        }
        
        // Act: Attempt login with different case
        $loginLower = $this->authService->login($email, strtolower($password));
        $loginUpper = $this->authService->login($email, strtoupper($password));
        
        // Assert: Both should fail (password is case-sensitive)
        $this->assertFalse(
            $loginLower['success'],
            'Login should fail with lowercase version of password'
        );
        $this->assertFalse(
            $loginUpper['success'],
            'Login should fail with uppercase version of password'
        );
        
        // Assert: Correct case should succeed
        $loginCorrect = $this->authService->login($email, $password);
        $this->assertTrue(
            $loginCorrect['success'],
            'Login should succeed with correct case password'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Special characters in password are handled correctly
     * 
     * @test
     */
    public function specialCharactersInPasswordAreHandled(): void
    {
        $this->forAll(
            Generator\elements([
                'p@ssw0rd!',
                'pass"word',
                "pass'word",
                'pass word',
                'pass\nword',
                'pass\tword',
                'пароль123',
                '密码123',
                '🔒password🔑'
            ])
        )
        ->then(function ($password) {
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            
            // Cleanup
            $this->cleanupUserByEmail($email);
            
            // Arrange: Register user with special character password
            $registerResult = $this->authService->register([
                'email' => $email,
                'password' => $password,
                'name' => 'Test User'
            ]);
            
            if (!$registerResult['success']) {
                $this->markTestSkipped('User registration failed');
                return;
            }
            
            // Act: Login with correct password
            $loginCorrect = $this->authService->login($email, $password);
            
            // Assert: Login should succeed with correct password
            $this->assertTrue(
                $loginCorrect['success'],
                'Login should succeed with correct special character password'
            );
            
            // Act: Login with slightly different password
            $wrongPassword = $password . 'x';
            $loginWrong = $this->authService->login($email, $wrongPassword);
            
            // Assert: Login should fail with wrong password
            $this->assertFalse(
                $loginWrong['success'],
                'Login should fail with incorrect password'
            );
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Failed login doesn't reveal user existence
     * 
     * Error messages should be the same for non-existent users and wrong passwords
     * 
     * @test
     */
    public function failedLoginDoesNotRevealUserExistence(): void
    {
        $existingEmail = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $nonExistentEmail = 'nonexistent_' . bin2hex(random_bytes(8)) . '@example.com';
        $password = 'password123';
        
        // Cleanup
        $this->cleanupUserByEmail($existingEmail);
        $this->cleanupUserByEmail($nonExistentEmail);
        
        // Arrange: Register user
        $registerResult = $this->authService->register([
            'email' => $existingEmail,
            'password' => $password,
            'name' => 'Test User'
        ]);
        
        if (!$registerResult['success']) {
            $this->markTestSkipped('User registration failed');
            return;
        }
        
        // Act: Login with wrong password for existing user
        $loginWrongPassword = $this->authService->login($existingEmail, 'wrong_password');
        
        // Act: Login with non-existent email
        $loginNonExistent = $this->authService->login($nonExistentEmail, $password);
        
        // Assert: Both should fail
        $this->assertFalse($loginWrongPassword['success']);
        $this->assertFalse($loginNonExistent['success']);
        
        // Assert: Error messages should be similar (not revealing which case it is)
        $error1 = strtolower($loginWrongPassword['error']);
        $error2 = strtolower($loginNonExistent['error']);
        
        // Both should contain generic terms like "invalid" or "incorrect"
        $this->assertTrue(
            str_contains($error1, 'invalid') || str_contains($error1, 'incorrect'),
            'Error for wrong password should be generic'
        );
        $this->assertTrue(
            str_contains($error2, 'invalid') || str_contains($error2, 'incorrect'),
            'Error for non-existent email should be generic'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($existingEmail);
    }

    /**
     * Property: Login with SQL injection attempts is rejected safely
     * 
     * @test
     */
    public function sqlInjectionAttemptsAreRejectedSafely(): void
    {
        $this->forAll(
            Generator\elements([
                "' OR '1'='1",
                "admin'--",
                "' OR 1=1--",
                "'; DROP TABLE users;--",
                "1' UNION SELECT * FROM users--"
            ])
        )
        ->then(function ($injectionAttempt) {
            // Act: Attempt login with SQL injection in email
            $loginEmail = $this->authService->login($injectionAttempt, 'password');
            
            // Assert: Login should fail safely
            $this->assertFalse(
                $loginEmail['success'],
                'Login should fail with SQL injection attempt in email'
            );
            $this->assertNull($loginEmail['session']);
            
            // Act: Attempt login with SQL injection in password
            $email = 'test@example.com';
            $loginPassword = $this->authService->login($email, $injectionAttempt);
            
            // Assert: Login should fail safely
            $this->assertFalse(
                $loginPassword['success'],
                'Login should fail with SQL injection attempt in password'
            );
            $this->assertNull($loginPassword['session']);
        });
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
