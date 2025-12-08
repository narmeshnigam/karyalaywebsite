<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\AuthService;
use Karyalay\Models\User;
use Karyalay\Models\PasswordResetToken;
use DateTime;

/**
 * Property-based tests for password reset token generation
 * 
 * Feature: karyalay-portal-system, Property 8: Password Reset Token Generation
 * Validates: Requirements 2.5
 */
class PasswordResetTokenPropertyTest extends TestCase
{
    use TestTrait;

    private AuthService $authService;
    private User $userModel;
    private PasswordResetToken $tokenModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
        $this->userModel = new User();
        $this->tokenModel = new PasswordResetToken();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Property 8: Password Reset Token Generation
     * 
     * For any registered user email, when a password reset is requested, 
     * a reset token should be generated and associated with that user account.
     * 
     * Validates: Requirements 2.5
     * 
     * @test
     */
    public function passwordResetTokenIsGeneratedForRegisteredUser(): void
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
            
            // Arrange: Create a user
            $registerResult = $this->authService->register([
                'email' => $email,
                'password' => $password,
                'name' => $name
            ]);
            
            if (!$registerResult['success']) {
                $this->markTestSkipped('User registration failed');
                return;
            }
            
            $user = $this->userModel->findByEmail($email);
            
            // Act: Request password reset
            $resetResult = $this->authService->requestPasswordReset($email);
            
            // Assert: Request succeeded
            $this->assertTrue(
                $resetResult['success'],
                'Password reset request should succeed for registered user'
            );
            
            // Assert: Token was created
            $this->assertNotNull(
                $resetResult['token'],
                'Token should be returned for registered user'
            );
            
            // Assert: Token has required fields
            $token = $resetResult['token'];
            $this->assertArrayHasKey('id', $token);
            $this->assertArrayHasKey('user_id', $token);
            $this->assertArrayHasKey('token', $token);
            $this->assertArrayHasKey('expires_at', $token);
            
            // Assert: Token is associated with correct user
            $this->assertEquals(
                $user['id'],
                $token['user_id'],
                'Token should be associated with the correct user'
            );
            
            // Assert: Token string is not empty and has reasonable length
            $this->assertNotEmpty($token['token']);
            $this->assertGreaterThan(
                32,
                strlen($token['token']),
                'Token should be sufficiently long for security'
            );
            
            // Assert: Token expires in approximately 1 hour
            $expiresAt = new DateTime($token['expires_at']);
            $now = new DateTime();
            $diff = $expiresAt->getTimestamp() - $now->getTimestamp();
            
            $this->assertGreaterThan(
                3500, // 58 minutes
                $diff,
                'Token should expire in approximately 1 hour (at least 58 minutes)'
            );
            $this->assertLessThan(
                3700, // 62 minutes
                $diff,
                'Token should expire in approximately 1 hour (at most 62 minutes)'
            );
            
            // Assert: Token can be validated
            $validatedToken = $this->tokenModel->validate($token['token']);
            $this->assertNotFalse(
                $validatedToken,
                'Token should be valid immediately after creation'
            );
            
            // Assert: Token is associated with user in database
            $dbToken = $this->tokenModel->findByUserId($user['id']);
            $this->assertNotFalse($dbToken);
            $this->assertEquals($token['token'], $dbToken['token']);
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Password reset for non-existent email doesn't reveal user existence
     * 
     * @test
     */
    public function passwordResetForNonExistentEmailDoesNotRevealExistence(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->then(function ($suffix) {
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            // Ensure email doesn't exist
            $this->cleanupUserByEmail($email);
            
            // Act: Request password reset for non-existent email
            $result = $this->authService->requestPasswordReset($email);
            
            // Assert: Request appears to succeed (for security)
            $this->assertTrue(
                $result['success'],
                'Password reset should appear to succeed even for non-existent email'
            );
            
            // Assert: No token is actually created
            $this->assertNull(
                $result['token'],
                'No token should be created for non-existent email'
            );
            
            // Assert: No error is returned (don't reveal user existence)
            $this->assertNull(
                $result['error'],
                'No error should be returned for non-existent email'
            );
        });
    }

    /**
     * Property: Multiple password reset requests replace old tokens
     * 
     * @test
     */
    public function multiplePasswordResetRequestsReplaceOldTokens(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // Arrange: Create user
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => 'password123',
            'name' => 'Test User'
        ]);
        
        if (!$registerResult['success']) {
            $this->markTestSkipped('User registration failed');
            return;
        }
        
        $user = $this->userModel->findByEmail($email);
        
        // Act: Request password reset multiple times
        $result1 = $this->authService->requestPasswordReset($email);
        $token1 = $result1['token']['token'] ?? null;
        
        $result2 = $this->authService->requestPasswordReset($email);
        $token2 = $result2['token']['token'] ?? null;
        
        $result3 = $this->authService->requestPasswordReset($email);
        $token3 = $result3['token']['token'] ?? null;
        
        // Assert: All requests succeeded
        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertTrue($result3['success']);
        
        // Assert: Tokens are different
        $this->assertNotEquals($token1, $token2);
        $this->assertNotEquals($token2, $token3);
        $this->assertNotEquals($token1, $token3);
        
        // Assert: Only the latest token is valid
        $this->assertFalse(
            $this->tokenModel->validate($token1),
            'First token should be invalidated'
        );
        $this->assertFalse(
            $this->tokenModel->validate($token2),
            'Second token should be invalidated'
        );
        $this->assertNotFalse(
            $this->tokenModel->validate($token3),
            'Latest token should be valid'
        );
        
        // Assert: Only one token exists for user
        $dbToken = $this->tokenModel->findByUserId($user['id']);
        $this->assertEquals($token3, $dbToken['token']);
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Password reset token can be used to reset password
     * 
     * @test
     */
    public function passwordResetTokenCanBeUsedToResetPassword(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        $originalPassword = 'original_password_123';
        $newPassword = 'new_password_456';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // Arrange: Create user
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => $originalPassword,
            'name' => 'Test User'
        ]);
        
        if (!$registerResult['success']) {
            $this->markTestSkipped('User registration failed');
            return;
        }
        
        // Request password reset
        $resetResult = $this->authService->requestPasswordReset($email);
        $token = $resetResult['token']['token'] ?? null;
        
        $this->assertNotNull($token);
        
        // Act: Reset password using token
        $result = $this->authService->resetPassword($token, $newPassword);
        
        // Assert: Password reset succeeded
        $this->assertTrue(
            $result['success'],
            'Password reset should succeed with valid token'
        );
        $this->assertNull($result['error']);
        
        // Assert: Can login with new password
        $loginResult = $this->authService->login($email, $newPassword);
        $this->assertTrue(
            $loginResult['success'],
            'Should be able to login with new password'
        );
        
        // Assert: Cannot login with old password
        $oldLoginResult = $this->authService->login($email, $originalPassword);
        $this->assertFalse(
            $oldLoginResult['success'],
            'Should not be able to login with old password'
        );
        
        // Assert: Token is invalidated after use
        $this->assertFalse(
            $this->tokenModel->validate($token),
            'Token should be invalidated after use'
        );
        
        // Cleanup
        $this->cleanupUserByEmail($email);
    }

    /**
     * Property: Expired tokens cannot be used
     * 
     * Note: This test simulates expiration by checking validation logic
     * 
     * @test
     */
    public function expiredTokensCannotBeUsed(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        
        // Cleanup
        $this->cleanupUserByEmail($email);
        
        // Arrange: Create user
        $registerResult = $this->authService->register([
            'email' => $email,
            'password' => 'password123',
            'name' => 'Test User'
        ]);
        
        if (!$registerResult['success']) {
            $this->markTestSkipped('User registration failed');
            return;
        }
        
        $user = $this->userModel->findByEmail($email);
        
        // Create a token that expires immediately (0 hours)
        $expiredToken = $this->tokenModel->create($user['id'], 0);
        
        // Wait a moment to ensure expiration
        sleep(1);
        
        // Act: Try to validate expired token
        $validatedToken = $this->tokenModel->validate($expiredToken['token']);
        
        // Assert: Expired token should not validate
        $this->assertFalse(
            $validatedToken,
            'Expired token should not validate'
        );
        
        // Act: Try to reset password with expired token
        $resetResult = $this->authService->resetPassword($expiredToken['token'], 'newpassword123');
        
        // Assert: Password reset should fail
        $this->assertFalse(
            $resetResult['success'],
            'Password reset should fail with expired token'
        );
        $this->assertNotNull($resetResult['error']);
        
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
            // Delete tokens first
            $this->tokenModel->deleteByUserId($user['id']);
            // Delete user
            $this->userModel->delete($user['id']);
        }
    }
}

