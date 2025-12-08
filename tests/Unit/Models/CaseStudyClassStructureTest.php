<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\CaseStudy;
use ReflectionClass;

/**
 * Test CaseStudy class structure and method signatures
 */
class CaseStudyClassStructureTest extends TestCase
{
    public function testCaseStudyClassExists(): void
    {
        $this->assertTrue(class_exists(CaseStudy::class), 'CaseStudy class should exist');
    }

    public function testCaseStudyClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(CaseStudy::class);
        
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
                "CaseStudy class should have {$method} method"
            );
        }
    }

    public function testCaseStudyCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(CaseStudy::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic(), 'create method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'create method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('data', $params[0]->getName(), 'First parameter should be named data');
        $this->assertTrue($params[0]->hasType(), 'data parameter should have type hint');
        $this->assertEquals('array', $params[0]->getType()->getName(), 'data parameter should be array');
    }

    public function testCaseStudyFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(CaseStudy::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic(), 'findById method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'findById method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertTrue($params[0]->hasType(), 'id parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'id parameter should be string');
    }

    public function testCaseStudyFindBySlugMethodSignature(): void
    {
        $reflection = new ReflectionClass(CaseStudy::class);
        $method = $reflection->getMethod('findBySlug');
        
        $this->assertTrue($method->isPublic(), 'findBySlug method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'findBySlug method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('slug', $params[0]->getName(), 'First parameter should be named slug');
        $this->assertTrue($params[0]->hasType(), 'slug parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'slug parameter should be string');
    }

    public function testCaseStudyUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(CaseStudy::class);
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

    public function testCaseStudyDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(CaseStudy::class);
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

    public function testCaseStudyFindAllMethodSignature(): void
    {
        $reflection = new ReflectionClass(CaseStudy::class);
        $method = $reflection->getMethod('findAll');
        
        $this->assertTrue($method->isPublic(), 'findAll method should be public');
        $this->assertGreaterThanOrEqual(0, $method->getNumberOfParameters(), 'findAll method should accept parameters');
        
        $this->assertTrue($method->hasReturnType(), 'findAll method should have return type');
        $this->assertEquals('array', $method->getReturnType()->getName(), 'findAll method should return array');
    }

    public function testCaseStudySlugExistsMethodSignature(): void
    {
        $reflection = new ReflectionClass(CaseStudy::class);
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
