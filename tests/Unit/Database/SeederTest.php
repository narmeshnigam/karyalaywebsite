<?php

namespace Karyalay\Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Karyalay\Database\Seeder;

class SeederTest extends TestCase
{
    public function test_seeder_class_exists(): void
    {
        $this->assertTrue(class_exists(Seeder::class));
    }

    public function test_seeder_has_run_all_method(): void
    {
        $this->assertTrue(method_exists(Seeder::class, 'runAll'));
    }

    public function test_seeder_constructor_accepts_pdo(): void
    {
        $reflection = new \ReflectionClass(Seeder::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());
        
        $params = $constructor->getParameters();
        $this->assertEquals('pdo', $params[0]->getName());
    }
}
