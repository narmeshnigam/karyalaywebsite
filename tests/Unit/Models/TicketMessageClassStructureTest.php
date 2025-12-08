<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\TicketMessage;
use ReflectionClass;

/**
 * Test TicketMessage class structure without database connection
 */
class TicketMessageClassStructureTest extends TestCase
{
    public function testTicketMessageClassExists(): void
    {
        $this->assertTrue(class_exists('Karyalay\Models\TicketMessage'));
    }

    public function testTicketMessageClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(TicketMessage::class);
        
        $requiredMethods = [
            'create',
            'findById',
            'findByTicketId',
            'findByAuthorId',
            'update',
            'delete',
            'findAll',
            'countByTicketId',
            'findCustomerVisibleByTicketId'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "TicketMessage class should have method: {$method}"
            );
        }
    }

    public function testTicketMessageCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(TicketMessage::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testTicketMessageFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(TicketMessage::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testTicketMessageUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(TicketMessage::class);
        $method = $reflection->getMethod('update');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(2, $method->getNumberOfParameters());
    }

    public function testTicketMessageDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(TicketMessage::class);
        $method = $reflection->getMethod('delete');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testTicketMessageCountByTicketIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(TicketMessage::class);
        $method = $reflection->getMethod('countByTicketId');
        
        $this->assertTrue($method->isPublic());
        $this->assertGreaterThanOrEqual(1, $method->getNumberOfParameters());
    }

    public function testTicketMessageFindCustomerVisibleByTicketIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(TicketMessage::class);
        $method = $reflection->getMethod('findCustomerVisibleByTicketId');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }
}
