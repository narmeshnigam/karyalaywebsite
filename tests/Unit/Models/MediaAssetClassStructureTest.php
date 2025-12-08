<?php

namespace Karyalay\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Karyalay\Models\MediaAsset;
use ReflectionClass;

/**
 * Test MediaAsset class structure without database connection
 */
class MediaAssetClassStructureTest extends TestCase
{
    public function testMediaAssetClassExists(): void
    {
        $this->assertTrue(class_exists('Karyalay\Models\MediaAsset'));
    }

    public function testMediaAssetClassHasRequiredMethods(): void
    {
        $reflection = new ReflectionClass(MediaAsset::class);
        
        $requiredMethods = [
            'create',
            'findById',
            'update',
            'delete',
            'findAll',
            'findByUploader'
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                $reflection->hasMethod($method),
                "MediaAsset class should have method: {$method}"
            );
        }
    }

    public function testMediaAssetCreateMethodSignature(): void
    {
        $reflection = new ReflectionClass(MediaAsset::class);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testMediaAssetFindByIdMethodSignature(): void
    {
        $reflection = new ReflectionClass(MediaAsset::class);
        $method = $reflection->getMethod('findById');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testMediaAssetUpdateMethodSignature(): void
    {
        $reflection = new ReflectionClass(MediaAsset::class);
        $method = $reflection->getMethod('update');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(2, $method->getNumberOfParameters());
    }

    public function testMediaAssetDeleteMethodSignature(): void
    {
        $reflection = new ReflectionClass(MediaAsset::class);
        $method = $reflection->getMethod('delete');
        
        $this->assertTrue($method->isPublic());
        $this->assertEquals(1, $method->getNumberOfParameters());
    }

    public function testMediaAssetFindByUploaderMethodSignature(): void
    {
        $reflection = new ReflectionClass(MediaAsset::class);
        $method = $reflection->getMethod('findByUploader');
        
        $this->assertTrue($method->isPublic());
        $this->assertGreaterThanOrEqual(1, $method->getNumberOfParameters());
    }
}
