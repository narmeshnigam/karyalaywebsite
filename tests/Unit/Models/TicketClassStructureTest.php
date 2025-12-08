<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\Ticket;
use ReflectionClass;

/**
 * Test Ticket class structure without database connection
 */
class TicketClassStructureTest extends TestCase
{
    public function testTicketClassExists(): void
    {
        $this->assertTrue(class_exists('Karyalay\Models\Ticket'));
    }

    public function testTicketClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(Ticket::class);
        
        $requiredMethods = [
            'create',
            'findById',
            'findByCustomerId',
            'findBySubscriptionId',
            'findByAssignedTo',
            'update',
            'delete',
            'findAll',
            'updateStatus',
            'assignTo',
            'isClosed'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Ticket class should have method: {$method}"
            );
        }
    }

    public function testTicketCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Ticket::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testTicketFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(Ticket::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testTicketUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Ticket::class);
        $method = $reflection->getMethod('update');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(2, $method->getNumberOfParameters());
    }

    public function testTicketDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(Ticket::class);
        $method = $reflection->getMethod('delete');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testTicketUpdateStatusMethodSignature(): void
    {
        $reflection = new ReflectionClass(Ticket::class);
        $method = $reflection->getMethod('updateStatus');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(2, $method->getNumberOfParameters());
    }

    public function testTicketAssignToMethodSignature(): void
    {
        $reflection = new ReflectionClass(Ticket::class);
        $method = $reflection->getMethod('assignTo');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(2, $method->getNumberOfParameters());
    }

    public function testTicketIsClosedMethodSignature(): void
    {
        $reflection = new ReflectionClass(Ticket::class);
        $method = $reflection->getMethod('isClosed');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }
}
