<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\Session;
use ReflectionClass;

/**
 * Test Session class structure without database connection
 */
class SessionClassStructureTest extends TestCase
{
    public function testSessionClassExists(): void
    {
        $this->assertTrue(class_exists('Karyalay\Models\Session'));
    }

    public function testSessionClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(Session::class);
        
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
                "Session class should have method: {$method}"
            );
        }
    }

    public function testSessionCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Session::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic());
        $this->assertGreaterThanOrEqual(1, $method->getNumberOfParameters());
    }

    public function testSessionValidateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Session::class);
        $method = $reflection->getMethod('validate');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }
}
