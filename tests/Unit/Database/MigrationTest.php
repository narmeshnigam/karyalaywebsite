<?php

namespace Karyalay\Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Karyalay\Database\Migration;

class MigrationTest extends TestCase
{
    public function test_migration_class_exists(): void
    {
        $this->assertTrue(class_exists(Migration::class));
    }

    public function test_migration_has_required_methods(): void
    {
        $this->assertTrue(method_exists(Migration::class, 'runAll'));
        $this->assertTrue(method_exists(Migration::class, 'reset'));
    }

    public function test_migration_constructor_accepts_pdo_and_path(): void
    {
        $reflection = new \ReflectionClass(Migration::class);
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor);
        $this->assertCount(2, $constructor->getParameters());
        
        $params = $constructor->getParameters();
        $this->assertEquals('pdo', $params[0]->getName());
        $this->assertEquals('migrationsPath', $params[1]->getName());
    }
}
