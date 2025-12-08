<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\User;
use ReflectionClass;

/**
 * Test User class structure without database connection
 */
class UserClassStructureTest extends TestCase
{
    public function testUserClassExists(): void
    {
        $this->assertTrue(class_exists('Karyalay\Models\User'));
    }

    public function testUserClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(User::class);
        
        $requiredMethods = [
            'create',
            'findById',
            'findByEmail',
            'update',
            'delete',
            'findAll',
            'emailExists',
            'verifyPassword'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "User class should have method: {$method}"
            );
        }
    }

    public function testUserCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(User::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testUserFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(User::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testUserUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(User::class);
        $method = $reflection->getMethod('update');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(2, $method->getNumberOfParameters());
    }

    public function testUserDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(User::class);
        $method = $reflection->getMethod('delete');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }
}
