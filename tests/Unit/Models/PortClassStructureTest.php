<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\Port;
use ReflectionClass;

/**
 * Test Port class structure and method signatures
 */
class PortClassStructureTest extends TestCase
{
    public function testPortClassExists(): void
    {
        $this->assertTrue(class_exists(Port::class), 'Port class should exist');
    }

    public function testPortClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(Port::class);
        
        $requiredMethods = [
            'create',
            'findById',
            'findBySubscriptionId',
            'findAvailable',
            'countAvailable',
            'update',
            'delete',
            'findAll',
            'updateStatus',
            'assignToSubscription',
            'release',
            'portExists'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Port class should have {$method} method"
            );
        }
    }

    public function testPortCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Port::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic(), 'create method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'create method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('data', $params[0]->getName(), 'First parameter should be named data');
        $this->assertTrue($params[0]->hasType(), 'data parameter should have type hint');
        $this->assertEquals('array', $params[0]->getType()->getName(), 'data parameter should be array');
    }

    public function testPortFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(Port::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic(), 'findById method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'findById method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertTrue($params[0]->hasType(), 'id parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'id parameter should be string');
    }

    public function testPortUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Port::class);
        $method = $reflection->getMethod('update');
        
        $this->assertTrue($method->isPublic(), 'update method should be public');
        $this->assertEquals(2, $method->getNumberOfParameters(), 'update method should accept 2 parameters');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertEquals('data', $params[1]->getName(), 'Second parameter should be named data');
        $this->assertTrue($params[0]->hasType(), 'id parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'id parameter should be string');
        $this->assertTrue($params[1]->hasType(), 'data parameter should have type hint');
        $this->assertEquals('array', $params[1]->getType()->getName(), 'data parameter should be array');
        
        $this->assertTrue($method->hasReturnType(), 'update method should have return type');
        $this->assertEquals('bool', $method->getReturnType()->getName(), 'update method should return bool');
    }

    public function testPortDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(Port::class);
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

    public function testPortAssignToSubscriptionMethodSignature(): void
    {
        $reflection = new ReflectionClass(Port::class);
        $method = $reflection->getMethod('assignToSubscription');
        
        $this->assertTrue($method->isPublic(), 'assignToSubscription method should be public');
        $this->assertEquals(4, $method->getNumberOfParameters(), 'assignToSubscription method should accept 4 parameters');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertEquals('subscriptionId', $params[1]->getName(), 'Second parameter should be named subscriptionId');
        $this->assertEquals('customerId', $params[2]->getName(), 'Third parameter should be named customerId');
        $this->assertEquals('assignedAt', $params[3]->getName(), 'Fourth parameter should be named assignedAt');
        
        $this->assertTrue($method->hasReturnType(), 'assignToSubscription method should have return type');
        $this->assertEquals('bool', $method->getReturnType()->getName(), 'assignToSubscription method should return bool');
    }

    public function testPortCountAvailableMethodSignature(): void
    {
        $reflection = new ReflectionClass(Port::class);
        $method = $reflection->getMethod('countAvailable');
        
        $this->assertTrue($method->isPublic(), 'countAvailable method should be public');
        $this->assertEquals(0, $method->getNumberOfParameters(), 'countAvailable method should accept 0 parameters');
        
        $this->assertTrue($method->hasReturnType(), 'countAvailable method should have return type');
        $this->assertEquals('int', $method->getReturnType()->getName(), 'countAvailable method should return int');
    }

    public function testPortFindAvailableMethodSignature(): void
    {
        $reflection = new ReflectionClass(Port::class);
        $method = $reflection->getMethod('findAvailable');
        
        $this->assertTrue($method->isPublic(), 'findAvailable method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'findAvailable method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('limit', $params[0]->getName(), 'First parameter should be named limit');
        $this->assertTrue($params[0]->hasType(), 'limit parameter should have type hint');
        $this->assertEquals('int', $params[0]->getType()->getName(), 'limit parameter should be int');
        $this->assertTrue($params[0]->isDefaultValueAvailable(), 'limit parameter should have default value');
        
        $this->assertTrue($method->hasReturnType(), 'findAvailable method should have return type');
        $this->assertEquals('array', $method->getReturnType()->getName(), 'findAvailable method should return array');
    }
}
