<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\PortAllocationLog;
use ReflectionClass;

/**
 * Test PortAllocationLog class structure and method signatures
 */
class PortAllocationLogClassStructureTest extends TestCase
{
    public function testPortAllocationLogClassExists(): void
    {
        $this->assertTrue(class_exists(PortAllocationLog::class), 'PortAllocationLog class should exist');
    }

    public function testPortAllocationLogClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(PortAllocationLog::class);
        
        $requiredMethods = [
            'create',
            'findById',
            'findByPortId',
            'findBySubscriptionId',
            'findByCustomerId',
            'findAll',
            'logAssignment',
            'logReassignment',
            'logRelease',
            'delete'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "PortAllocationLog class should have {$method} method"
            );
        }
    }

    public function testPortAllocationLogCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(PortAllocationLog::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic(), 'create method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'create method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('data', $params[0]->getName(), 'First parameter should be named data');
        $this->assertTrue($params[0]->hasType(), 'data parameter should have type hint');
        $this->assertEquals('array', $params[0]->getType()->getName(), 'data parameter should be array');
    }

    public function testPortAllocationLogFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(PortAllocationLog::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic(), 'findById method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'findById method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertTrue($params[0]->hasType(), 'id parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'id parameter should be string');
    }

    public function testPortAllocationLogLogAssignmentMethodSignature(): void
    {
        $reflection = new ReflectionClass(PortAllocationLog::class);
        $method = $reflection->getMethod('logAssignment');
        
        $this->assertTrue($method->isPublic(), 'logAssignment method should be public');
        $this->assertEquals(4, $method->getNumberOfParameters(), 'logAssignment method should accept 4 parameters');
        
        $params = $method->getParameters();
        $this->assertEquals('portId', $params[0]->getName(), 'First parameter should be named portId');
        $this->assertEquals('subscriptionId', $params[1]->getName(), 'Second parameter should be named subscriptionId');
        $this->assertEquals('customerId', $params[2]->getName(), 'Third parameter should be named customerId');
        $this->assertEquals('performedBy', $params[3]->getName(), 'Fourth parameter should be named performedBy');
        
        $this->assertTrue($params[0]->hasType(), 'portId parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'portId parameter should be string');
        $this->assertTrue($params[1]->hasType(), 'subscriptionId parameter should have type hint');
        $this->assertEquals('string', $params[1]->getType()->getName(), 'subscriptionId parameter should be string');
        $this->assertTrue($params[2]->hasType(), 'customerId parameter should have type hint');
        $this->assertEquals('string', $params[2]->getType()->getName(), 'customerId parameter should be string');
        $this->assertTrue($params[3]->allowsNull(), 'performedBy parameter should allow null');
    }

    public function testPortAllocationLogLogReassignmentMethodSignature(): void
    {
        $reflection = new ReflectionClass(PortAllocationLog::class);
        $method = $reflection->getMethod('logReassignment');
        
        $this->assertTrue($method->isPublic(), 'logReassignment method should be public');
        $this->assertEquals(4, $method->getNumberOfParameters(), 'logReassignment method should accept 4 parameters');
        
        $params = $method->getParameters();
        $this->assertEquals('portId', $params[0]->getName(), 'First parameter should be named portId');
        $this->assertEquals('subscriptionId', $params[1]->getName(), 'Second parameter should be named subscriptionId');
        $this->assertEquals('customerId', $params[2]->getName(), 'Third parameter should be named customerId');
        $this->assertEquals('performedBy', $params[3]->getName(), 'Fourth parameter should be named performedBy');
        
        $this->assertTrue($params[0]->hasType(), 'portId parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'portId parameter should be string');
        $this->assertTrue($params[1]->hasType(), 'subscriptionId parameter should have type hint');
        $this->assertEquals('string', $params[1]->getType()->getName(), 'subscriptionId parameter should be string');
        $this->assertTrue($params[2]->hasType(), 'customerId parameter should have type hint');
        $this->assertEquals('string', $params[2]->getType()->getName(), 'customerId parameter should be string');
        $this->assertTrue($params[3]->hasType(), 'performedBy parameter should have type hint');
        $this->assertEquals('string', $params[3]->getType()->getName(), 'performedBy parameter should be string');
    }

    public function testPortAllocationLogDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(PortAllocationLog::class);
        $method = $reflection->getMethod('delete');
        
        $this->assertTrue($method->isPublic(), 'delete method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'delete method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertTrue($params[0]->hasType(), 'id parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'id parameter should be string');
        
        $this->assertTrue($method->hasReturnType(), 'delete method should have return type');
        $this->assertEquals('bool', $method->getReturnType()->getName(), 'delete method should return bool');
    }
}
