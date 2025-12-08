<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\AuthService;
use Karyalay\Models\User;

/**
 * Property-based tests for user registration
 * 
 * Feature: karyalay-portal-system, Property 4: User Registration with Unique Email
 * Validates: Requirements 2.1
 */
class UserRegistrationPropertyTest extends TestCase
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
        // Clean up any test users created during tests
        parent::tearDown();
    }

    /**
     * Property 4: User Registration with Unique Email
     * 
     * For any valid registration data with a unique email, when the registration 
     * form is submitted, a new user account should be created with a hashed password.
     * 
     * Validates: Requirements 2.1
     * 
     * @test
     */
    public function userRegistrationCreatesAccountWithHashedPassword(): void
    {
        $this->forAll(
            Generator\choose(8, 50),  // password length
            Generator\choose(1, 50),  // name length
            Generator\choose(10, 20)  // phone length
        )
        ->then(function ($passwordLen, $nameLen, $phoneLen) {
            // Generate strings of specified lengths
            $password = str_repeat('a', $passwordLen);
            $name = 'User' . str_repeat('X', max(0, $nameLen - 4));
            $phone = str_repeat('1', $phoneLen);
            // Generate unique email
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            $userData = [
                'email' => $email,
                'password' => $password,
                'name' => $name,
                'phone' => $phone
            ];
            
            // Ensure email doesn't exist before test
            $this->cleanupUserByEmail($email);
            
            // Act: Register user
            $result = $this->authService->register($userData);
            
            // Assert: Registration succeeded
            $this->assertTrue(
                $result['success'],
                'Registration should succeed with valid unique email'
            );
            $this->assertNull($result['error'], 'No error should be returned');
            $this->assertNotNull($result['user'], 'User data should be returned');
            
            // Assert: User was created in database
            $user = $this->userModel->findByEmail($email);
            $this->assertNotFalse($user, 'User should exist in database');
            $this->assertEquals($email, $user['email']);
            $this->assertEquals($name, $user['name']);
            
            // Assert: Password is hashed (not plaintext)
            $this->assertNotEquals(
                $password,
                $user['password_hash'],
                'Password should be hashed, not stored in plaintext'
            );
            
            // Assert: Password hash is valid bcrypt
            $this->assertMatchesRegularExpression(
                '/^\$2y\$\d{2}\$.{53}$/',
                $user['password_hash'],
                'Password hash should be valid bcrypt format'
            );
            
            // Assert: Password can be verified
            $this->assertTrue(
                password_verify($password, $user['password_hash']),
                'Original password should verify against stored hash'
            );
            
            // Assert: User has CUSTOMER role by default
            $this->assertEquals('CUSTOMER', $user['role']);
            
            // Assert: Email is not verified by default
            $this->assertFalse((bool)$user['email_verified']);
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        });
    }

    /**
     * Property: Registration with missing required fields should fail
     * 
     * @test
     */
    public function registrationWithMissingFieldsFails(): void
    {
        // Test missing email
        $result = $this->authService->register([
            'password' => 'password123',
            'name' => 'Test User'
        ]);
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
        
        // Test missing password
        $result = $this->authService->register([
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
        
        // Test missing name
        $result = $this->authService->register([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }

    /**
     * Property: Registration with invalid email format should fail
     * 
     * @test
     */
    public function registrationWithInvalidEmailFails(): void
    {
        $invalidEmails = [
            'not-an-email',
            'missing@domain',
            '@nodomain.com',
            'spaces in@email.com',
            'double@@domain.com'
        ];
        
        foreach ($invalidEmails as $invalidEmail) {
            $result = $this->authService->register([
                'email' => $invalidEmail,
                'password' => 'password123',
                'name' => 'Test User'
            ]);
            
            $this->assertFalse(
                $result['success'],
                'Registration should fail with invalid email format: ' . $invalidEmail
            );
            $this->assertNotNull($result['error']);
        }
    }

    /**
     * Property: Registration with weak password should fail
     * 
     * @test
     */
    public function registrationWithWeakPasswordFails(): void
    {
        $this->forAll(
            Generator\choose(0, 7)
        )
        ->then(function ($length) {
            // Generate a password of the specified weak length
            $weakPassword = str_repeat('a', $length);
            $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
            
            $result = $this->authService->register([
                'email' => $email,
                'password' => $weakPassword,
                'name' => 'Test User'
            ]);
            
            $this->assertFalse(
                $result['success'],
                'Registration should fail with password shorter than 8 characters'
            );
            $this->assertNotNull($result['error']);
        });
    }

    /**
     * Property: User data should not include password_hash in response
     * 
     * @test
     */
    public function registrationResponseDoesNotIncludePasswordHash(): void
    {
        $email = 'test_' . bin2hex(random_bytes(8)) . '@example.com';
        
        $result = $this->authService->register([
            'email' => $email,
            'password' => 'password123',
            'name' => 'Test User'
        ]);
        
        if ($result['success']) {
            $this->assertArrayNotHasKey(
                'password_hash',
                $result['user'],
                'Response should not include password_hash'
            );
            
            // Cleanup
            $this->cleanupUserByEmail($email);
        }
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

