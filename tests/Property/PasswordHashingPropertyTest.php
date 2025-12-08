<?php

namespace Karyalay\Tests\Property;

use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use Karyalay\Services\PasswordHashService;

/**
 * Property-based tests for password hashing
 * 
 * Feature: karyalay-portal-system, Property 41: Password Hashing
 * Validates: Requirements 13.1
 */
class PasswordHashingPropertyTest extends TestCase
{
    use TestTrait;

    private PasswordHashService $passwordHashService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passwordHashService = new PasswordHashService(12);
    }

    /**
     * Property 41: Password Hashing
     * 
     * For any password, when it is stored in the database, it should be hashed 
     * using bcrypt or argon2 algorithm and the plaintext password should never be stored.
     * 
     * Validates: Requirements 13.1
     * 
     * @test
     */
    public function passwordsAreHashedAndNeverStoredInPlaintext(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($password) {
            // Only test passwords with at least 8 characters
            return strlen($password) >= 8 && strlen($password) <= 100;
        })
        ->then(function ($password) {
            // Hash the password
            $hash = $this->passwordHashService->hash($password);
            
            // Assert hash was created successfully
            $this->assertNotFalse($hash, 'Password hashing should succeed');
            
            // Assert hash is not the same as plaintext password
            $this->assertNotEquals(
                $password,
                $hash,
                'Hashed password should not equal plaintext password'
            );
            
            // Assert hash is a valid bcrypt hash (starts with $2y$ and has proper length)
            $this->assertMatchesRegularExpression(
                '/^\$2y\$\d{2}\$.{53}$/',
                $hash,
                'Hash should be a valid bcrypt hash'
            );
            
            // Assert the hash can be verified with the original password
            $this->assertTrue(
                $this->passwordHashService->verify($password, $hash),
                'Original password should verify against the hash'
            );
            
            // Assert a different password does not verify
            // Use a completely different password to avoid edge cases
            $differentPassword = 'COMPLETELY_DIFFERENT_' . bin2hex(random_bytes(16));
            $this->assertFalse(
                $this->passwordHashService->verify($differentPassword, $hash),
                'Different password should not verify against the hash'
            );
            
            // Assert hash info shows bcrypt algorithm
            $info = $this->passwordHashService->getInfo($hash);
            $this->assertEquals(
                PASSWORD_BCRYPT,
                $info['algo'],
                'Hash algorithm should be bcrypt'
            );
            
            // Assert cost factor is 12
            $this->assertEquals(
                12,
                $info['options']['cost'] ?? 0,
                'Bcrypt cost factor should be 12'
            );
        });
    }

    /**
     * Property: Password hashing is deterministic for verification but non-deterministic for generation
     * 
     * For any password, hashing it twice should produce different hashes (due to salt),
     * but both hashes should verify the original password.
     * 
     * @test
     */
    public function passwordHashingUsesUniqueSalts(): void
    {
        $this->forAll(
            Generator\string()
        )
        ->when(function ($password) {
            // Only test passwords with at least 8 characters
            return strlen($password) >= 8 && strlen($password) <= 100;
        })
        ->then(function ($password) {
            // Hash the same password twice
            $hash1 = $this->passwordHashService->hash($password);
            $hash2 = $this->passwordHashService->hash($password);
            
            // Assert both hashes are different (unique salts)
            $this->assertNotEquals(
                $hash1,
                $hash2,
                'Two hashes of the same password should be different due to unique salts'
            );
            
            // Assert both hashes verify the original password
            $this->assertTrue(
                $this->passwordHashService->verify($password, $hash1),
                'First hash should verify the password'
            );
            $this->assertTrue(
                $this->passwordHashService->verify($password, $hash2),
                'Second hash should verify the password'
            );
        });
    }

    /**
     * Property: Empty passwords should be handled
     * 
     * Edge case: Empty strings should still be hashable
     * 
     * @test
     */
    public function emptyPasswordsCanBeHashed(): void
    {
        $password = '';
        $hash = $this->passwordHashService->hash($password);
        
        $this->assertNotFalse($hash, 'Empty password should be hashable');
        $this->assertTrue(
            $this->passwordHashService->verify($password, $hash),
            'Empty password should verify against its hash'
        );
    }

    /**
     * Property: Very long passwords should be handled
     * 
     * Edge case: Passwords up to reasonable length should be hashable
     * 
     * @test
     */
    public function veryLongPasswordsCanBeHashed(): void
    {
        $password = str_repeat('a', 1000);
        $hash = $this->passwordHashService->hash($password);
        
        $this->assertNotFalse($hash, 'Very long password should be hashable');
        $this->assertTrue(
            $this->passwordHashService->verify($password, $hash),
            'Very long password should verify against its hash'
        );
    }

    /**
     * Property: Special characters in passwords should be handled
     * 
     * Edge case: Passwords with special characters, unicode, etc.
     * 
     * @test
     */
    public function specialCharactersInPasswordsAreHandled(): void
    {
        $this->forAll(
            Generator\elements([
                'p@ssw0rd!',
                'пароль',
                '密码',
                '🔒🔑',
                "pass'word\"with<>quotes",
                'pass\nword\twith\rwhitespace',
                'pass word with spaces'
            ])
        )
        ->then(function ($password) {
            $hash = $this->passwordHashService->hash($password);
            
            $this->assertNotFalse($hash, 'Password with special characters should be hashable');
            $this->assertTrue(
                $this->passwordHashService->verify($password, $hash),
                'Password with special characters should verify against its hash'
            );
        });
    }
}

