<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\PasswordResetToken;
use ReflectionClass;

/**
 * Test PasswordResetToken class structure without database connection
 */
class PasswordResetTokenClassStructureTest extends TestCase
{
    public function testPasswordResetTokenClassExists(): void
    {
        $this->assertTrue(class_exists('Karyalay\Models\PasswordResetToken'));
    }

    public function testPasswordResetTokenClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(PasswordResetToken::class);
        
        $requiredMethods = [
            'create',
            'findById',
            'findByToken',
            'findByUserId',
            'validate',
            'delete',
            'deleteByToken',
            'deleteByUserId',
            'deleteExpired'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "PasswordResetToken class should have method: {$method}"
            );
        }
    }

    public function testPasswordResetTokenCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(PasswordResetToken::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic());
        $this->assertGreaterThanOrEqual(1, $method->getNumberOfParameters());
    }

    public function testPasswordResetTokenValidateMethodSignature(): void
    {
        $reflection = new ReflectionClass(PasswordResetToken::class);
        $method = $reflection->getMethod('validate');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }
}
