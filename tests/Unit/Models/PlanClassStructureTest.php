<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\Plan;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test Plan class structure and method signatures
 */
class PlanClassStructureTest extends TestCase
{
    public function testPlanClassExists(): void
    {
        $this->assertTrue(class_exists(Plan::class), 'Plan class should exist');
    }

    public function testPlanClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(Plan::class);
        
        $requiredMethods = [
            'create',
            'findById',
            'findBySlug',
            'update',
            'delete',
            'findAll',
            'slugExists'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "Plan class should have {$method} method"
            );
        }
    }

    public function testPlanCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Plan::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic(), 'create method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'create method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('data', $params[0]->getName(), 'First parameter should be named data');
        $this->assertTrue($params[0]->hasType(), 'data parameter should have type hint');
        $this->assertEquals('array', $params[0]->getType()->getName(), 'data parameter should be array');
    }

    public function testPlanFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(Plan::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic(), 'findById method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'findById method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertTrue($params[0]->hasType(), 'id parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'id parameter should be string');
    }

    public function testPlanUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Plan::class);
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

    public function testPlanDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(Plan::class);
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
