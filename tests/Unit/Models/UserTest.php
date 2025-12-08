<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\User;
use Karyalay\Database\Connection;

class UserTest extends TestCase
{
    private User $userModel;
    private static array $testUserIds = [];

    public static function setUpBeforeClass(): void
    {
        // Load environment variables
        if (file_exists(__DIR__ . '/../../../.env')) {
            $lines = file(__DIR__ . '/../../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                list($name, $value) = explode('=', $line, 2);
                $_ENV[trim($name)] = trim($value);
            }
        }
    }

    protected function setUp(): void
    {
        $this->userModel = new User();
    }

    protected function tearDown(): void
    {
        // Clean up test users
        foreach (self::$testUserIds as $id) {
            $this->userModel->delete($id);
        }
        self::$testUserIds = [];
    }

    public function testCreateUser(): void
    {
        $userData = [
            'email' => 'test' . time() . '@example.com',
            'password' => 'SecurePassword123!',
            'name' => 'Test User',
            'phone' => '1234567890',
            'business_name' => 'Test Business',
            'role' => 'CUSTOMER'
        ];

        $user = $this->userModel->create($userData);

        $this->assertIsArray($user);
        $this->assertArrayHasKey('id', $user);
        $this->assertEquals($userData['email'], $user['email']);
        $this->assertEquals($userData['name'], $user['name']);
        $this->assertNotEquals($userData['password'], $user['password_hash']);
        
        self::$testUserIds[] = $user['id'];
    }

    public function testFindUserById(): void
    {
        // Create a test user
        $userData = [
            'email' => 'findbyid' . time() . '@example.com',
            'password' => 'SecurePassword123!',
            'name' => 'Find By ID User'
        ];

        $createdUser = $this->userModel->create($userData);
        self::$testUserIds[] = $createdUser['id'];

        // Find the user
        $foundUser = $this->userModel->findById($createdUser['id']);

        $this->assertIsArray($foundUser);
        $this->assertEquals($createdUser['id'], $foundUser['id']);
        $this->assertEquals($userData['email'], $foundUser['email']);
    }

    public function testFindUserByEmail(): void
    {
        // Create a test user
        $userData = [
            'email' => 'findbyemail' . time() . '@example.com',
            'password' => 'SecurePassword123!',
            'name' => 'Find By Email User'
        ];

        $createdUser = $this->userModel->create($userData);
        self::$testUserIds[] = $createdUser['id'];

        // Find the user
        $foundUser = $this->userModel->findByEmail($userData['email']);

        $this->assertIsArray($foundUser);
        $this->assertEquals($createdUser['id'], $foundUser['id']);
        $this->assertEquals($userData['email'], $foundUser['email']);
    }

    public function testUpdateUser(): void
    {
        // Create a test user
        $userData = [
            'email' => 'update' . time() . '@example.com',
            'password' => 'SecurePassword123!',
            'name' => 'Update User'
        ];

        $user = $this->userModel->create($userData);
        self::$testUserIds[] = $user['id'];

        // Update the user
        $updateData = [
            'name' => 'Updated Name',
            'phone' => '9876543210'
        ];

        $result = $this->userModel->update($user['id'], $updateData);
        $this->assertTrue($result);

        // Verify the update
        $updatedUser = $this->userModel->findById($user['id']);
        $this->assertEquals($updateData['name'], $updatedUser['name']);
        $this->assertEquals($updateData['phone'], $updatedUser['phone']);
    }

    public function testDeleteUser(): void
    {
        // Create a test user
        $userData = [
            'email' => 'delete' . time() . '@example.com',
            'password' => 'SecurePassword123!',
            'name' => 'Delete User'
        ];

        $user = $this->userModel->create($userData);

        // Delete the user
        $result = $this->userModel->delete($user['id']);
        $this->assertTrue($result);

        // Verify deletion
        $deletedUser = $this->userModel->findById($user['id']);
        $this->assertFalse($deletedUser);
    }

    public function testEmailExists(): void
    {
        // Create a test user
        $userData = [
            'email' => 'emailexists' . time() . '@example.com',
            'password' => 'SecurePassword123!',
            'name' => 'Email Exists User'
        ];

        $user = $this->userModel->create($userData);
        self::$testUserIds[] = $user['id'];

        // Check if email exists
        $exists = $this->userModel->emailExists($userData['email']);
        $this->assertTrue($exists);

        // Check non-existent email
        $notExists = $this->userModel->emailExists('nonexistent@example.com');
        $this->assertFalse($notExists);
    }

    public function testVerifyPassword(): void
    {
        // Create a test user
        $password = 'SecurePassword123!';
        $userData = [
            'email' => 'verifypass' . time() . '@example.com',
            'password' => $password,
            'name' => 'Verify Password User'
        ];

        $user = $this->userModel->create($userData);
        self::$testUserIds[] = $user['id'];

        // Verify correct password
        $verifiedUser = $this->userModel->verifyPassword($userData['email'], $password);
        $this->assertIsArray($verifiedUser);
        $this->assertEquals($user['id'], $verifiedUser['id']);

        // Verify incorrect password
        $failedVerification = $this->userModel->verifyPassword($userData['email'], 'WrongPassword');
        $this->assertFalse($failedVerification);
    }

    public function testPasswordHashing(): void
    {
        // Create a test user
        $password = 'SecurePassword123!';
        $userData = [
            'email' => 'passhash' . time() . '@example.com',
            'password' => $password,
            'name' => 'Password Hash User'
        ];

        $user = $this->userModel->create($userData);
        self::$testUserIds[] = $user['id'];

        // Verify password is hashed
        $this->assertNotEquals($password, $user['password_hash']);
        $this->assertTrue(password_verify($password, $user['password_hash']));
        
        // Verify bcrypt is used
        $this->assertStringStartsWith('$2y$', $user['password_hash']);
    }
}
