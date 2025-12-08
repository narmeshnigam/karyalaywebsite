<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\BlogPost;
use ReflectionClass;

/**
 * Test BlogPost class structure and method signatures
 */
class BlogPostClassStructureTest extends TestCase
{
    public function testBlogPostClassExists(): void
    {
        $this->assertTrue(class_exists(BlogPost::class), 'BlogPost class should exist');
    }

    public function testBlogPostClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(BlogPost::class);
        
        $requiredMethods = [
            'create',
            'findById',
            'findBySlug',
            'findByAuthorId',
            'update',
            'delete',
            'findAll',
            'slugExists',
            'publish',
            'unpublish'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "BlogPost class should have {$method} method"
            );
        }
    }

    public function testBlogPostCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(BlogPost::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic(), 'create method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'create method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('data', $params[0]->getName(), 'First parameter should be named data');
        $this->assertTrue($params[0]->hasType(), 'data parameter should have type hint');
        $this->assertEquals('array', $params[0]->getType()->getName(), 'data parameter should be array');
    }

    public function testBlogPostFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(BlogPost::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic(), 'findById method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'findById method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertTrue($params[0]->hasType(), 'id parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'id parameter should be string');
    }

    public function testBlogPostFindBySlugMethodSignature(): void
    {
        $reflection = new ReflectionClass(BlogPost::class);
        $method = $reflection->getMethod('findBySlug');
        
        $this->assertTrue($method->isPublic(), 'findBySlug method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'findBySlug method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('slug', $params[0]->getName(), 'First parameter should be named slug');
        $this->assertTrue($params[0]->hasType(), 'slug parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'slug parameter should be string');
    }

    public function testBlogPostFindByAuthorIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(BlogPost::class);
        $method = $reflection->getMethod('findByAuthorId');
        
        $this->assertTrue($method->isPublic(), 'findByAuthorId method should be public');
        $this->assertGreaterThanOrEqual(1, $method->getNumberOfParameters(), 'findByAuthorId method should accept at least 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('authorId', $params[0]->getName(), 'First parameter should be named authorId');
        $this->assertTrue($params[0]->hasType(), 'authorId parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'authorId parameter should be string');
        
        $this->assertTrue($method->hasReturnType(), 'findByAuthorId method should have return type');
        $this->assertEquals('array', $method->getReturnType()->getName(), 'findByAuthorId method should return array');
    }

    public function testBlogPostUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(BlogPost::class);
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

    public function testBlogPostDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(BlogPost::class);
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

    public function testBlogPostPublishMethodSignature(): void
    {
        $reflection = new ReflectionClass(BlogPost::class);
        $method = $reflection->getMethod('publish');
        
        $this->assertTrue($method->isPublic(), 'publish method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'publish method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertTrue($params[0]->hasType(), 'id parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'id parameter should be string');
        
        $this->assertTrue($method->hasReturnType(), 'publish method should have return type');
        $this->assertEquals('bool', $method->getReturnType()->getName(), 'publish method should return bool');
    }

    public function testBlogPostUnpublishMethodSignature(): void
    {
        $reflection = new ReflectionClass(BlogPost::class);
        $method = $reflection->getMethod('unpublish');
        
        $this->assertTrue($method->isPublic(), 'unpublish method should be public');
        $this->assertEquals(1, $method->getNumberOfParameters(), 'unpublish method should accept 1 parameter');
        
        $params = $method->getParameters();
        $this->assertEquals('id', $params[0]->getName(), 'First parameter should be named id');
        $this->assertTrue($params[0]->hasType(), 'id parameter should have type hint');
        $this->assertEquals('string', $params[0]->getType()->getName(), 'id parameter should be string');
        
        $this->assertTrue($method->hasReturnType(), 'unpublish method should have return type');
        $this->assertEquals('bool', $method->getReturnType()->getName(), 'unpublish method should return bool');
    }
}
