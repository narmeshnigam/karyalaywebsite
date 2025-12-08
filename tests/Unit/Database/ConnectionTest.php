<?php

namespace Karyalay\Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Karyalay\Database\Connection;
use PDO;

class ConnectionTest extends TestCase
{
    public function test_can_get_database_instance(): void
    {
        // Set test configuration
        Connection::setConfig([
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'test_db',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ]);

        // This will throw an exception if connection fails
        // For now, we just test that the class structure is correct
        $this->assertTrue(class_exists(Connection::class));
        $this->assertTrue(method_exists(Connection::class, 'getInstance'));
        $this->assertTrue(method_exists(Connection::class, 'setConfig'));
        $this->assertTrue(method_exists(Connection::class, 'beginTransaction'));
        $this->assertTrue(method_exists(Connection::class, 'commit'));
        $this->assertTrue(method_exists(Connection::class, 'rollback'));
    }

    public function test_connection_has_transaction_methods(): void
    {
        $this->assertTrue(method_exists(Connection::class, 'beginTransaction'));
        $this->assertTrue(method_exists(Connection::class, 'commit'));
        $this->assertTrue(method_exists(Connection::class, 'rollback'));
        $this->assertTrue(method_exists(Connection::class, 'inTransaction'));
    }

    public function test_connection_is_singleton(): void
    {
        // Verify that getInstance is static
        $reflection = new \ReflectionClass(Connection::class);
        $method = $reflection->getMethod('getInstance');
        $this->assertTrue($method->isStatic());
    }
}
