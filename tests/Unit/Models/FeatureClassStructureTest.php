<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\Feature;
use ReflectionClass;

/**
 * Test Feature class structure and method signatures
 */
class FeatureClassStructureTest extends TestCase
{
    public function testFeatureClassExists(): void
    {
        $this->assertTrue(class_exists(Feature::class), 'Feature class should exist');
    }

    public function testFeatureClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(Feature::class);
        
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
                "Feature class should have {$method} method"
            );
        }
    }

    public function testFeatureCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Feature::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic(), 'create method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'create method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('data', $params[0]->getName(), 'First parameter should be named data');
        $this->assertTrue($params[0]->hasType(), 'data parameter should have type hint');
        $this->assertEquals('array', $params[0]->getType()->getName(), 'data parameter should be array');
    }

    public function testFeatureFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(Feature::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic(), 'findById method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'findById method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertTrue($params[0]->hasType(), 'id parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'id parameter should be string');
    }

    public function testFeatureFindBySlugMethodSignature(): void
    {
        $reflection = new ReflectionClass(Feature::class);
        $method = $reflection->getMethod('findBySlug');
        
        $this->assertTrue($method->isPublic(), 'findBySlug method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'findBySlug method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('slug', $params[0]->getName(), 'First parameter should be named slug');
        $this->assertTrue($params[0]->hasType(), 'slug parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'slug parameter should be string');
    }

    public function testFeatureUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(Feature::class);
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

    public function testFeatureDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(Feature::class);
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

    public function testFeatureFindAllMethodSignature(): void
    {
        $reflection = new ReflectionClass(Feature::class);
        $method = $reflection->getMethod('findAll');
        
        $this->assertTrue($method->isPublic(), 'findAll method should be public');
        $this->assertGreaterThanOrEqual(0, $method->getNumberOfParameters(), 'findAll method should accept parameters');
        
        $this->assertTrue($method->hasReturnType(), 'findAll method should have return type');
        $this->assertEquals('array', $method->getReturnType()->getName(), 'findAll method should return array');
    }

    public function testFeatureSlugExistsMethodSignature(): void
    {
        $reflection = new ReflectionClass(Feature::class);
        $method = $reflection->getMethod('slugExists');
        
        $this->assertTrue($method->isPublic(), 'slugExists method should be public');
        $this->assertGreaterThanOrEqual(1, $method->getNumberOfParameters(), 'slugExists method should accept at least 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('slug', $params[0]->getName(), 'First parameter should be named slug');
        $this->assertTrue($params[0]->hasType(), 'slug parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'slug parameter should be string');
        
        $this->assertTrue($method->hasReturnType(), 'slugExists method should have return type');
        $this->assertEquals('bool', $method->getReturnType()->getName(), 'slugExists method should return bool');
    }
}
