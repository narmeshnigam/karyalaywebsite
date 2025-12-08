<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\Lead;
use ReflectionClass;

/**
 * Test Lead class structure without database connection
 */
class LeadClassStructureTest extends TestCase
{
    public function testLeadClassExists(): void
    {
        $this->assertTrue(class_exists('Karyalay\Models\Lead'));
    }

    public function testLeadClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(Lead::class);
        
        $requiredMethods = [
            'create',
            'findById',
            'update',
            'delete',
            'findAll',
            'markAsContacted'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Lead class should have method: {$method}"
            );
        }
    }

    public function testLeadCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Lead::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testLeadFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(Lead::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testLeadUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Lead::class);
        $method = $reflection->getMethod('update');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(2, $method->getNumberOfParameters());
    }

    public function testLeadDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(Lead::class);
        $method = $reflection->getMethod('delete');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testLeadMarkAsContactedMethodSignature(): void
    {
        $reflection = new ReflectionClass(Lead::class);
        $method = $reflection->getMethod('markAsContacted');
        
        $this->assertTrue($method->isPublic());
        $this->assertGreaterThanOrEqual(1, $method->getNumberOfParameters());
    }
}
