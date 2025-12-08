#!/usr/bin/env php
<?php

/**
 * Database Connection Check Script
 * Tests database connectivity and displays connection information
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

echo "Checking database connection...\n";
echo "================================\n\n";

try {
    // Load database configuration
    $config = require __DIR__ . '/../config/database.php';
    
    echo "Configuration:\n";
    echo "  Host: " . $config['host'] . "\n";
    echo "  Port: " . $config['port'] . "\n";
    echo "  Database: " . $config['database'] . "\n";
    echo "  Username: " . $config['username'] . "\n";
    echo "\n";
    
    // Test connection
    $db = Connection::getInstance();
    
    // Run test query
    $result = $db->query('SELECT VERSION() as version, DATABASE() as database, USER() as user');
    $info = $result->fetch();
    
    echo "✓ Connection successful!\n\n";
    echo "Database Information:\n";
    echo "  MySQL Version: " . $info['version'] . "\n";
    echo "  Current Database: " . $info['database'] . "\n";
    echo "  Current User: " . $info['user'] . "\n";
    echo "\n";
    
    // Check tables
    $result = $db->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        echo "  - " . $table . "\n";
    }
    echo "\n";
    
    // Check connection pool
    echo "Connection Settings:\n";
    echo "  Persistent: " . ($config['options'][PDO::ATTR_PERSISTENT] ? 'Yes' : 'No') . "\n";
    echo "  Charset: " . $config['charset'] . "\n";
    echo "  Collation: " . $config['collation'] . "\n";
    echo "\n";
    
    echo "✓ Database check completed successfully\n";
    exit(0);
    
} catch (PDOException $e) {
    echo "✗ Database connection failed!\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "\n";
    echo "Troubleshooting:\n";
    echo "  1. Check database credentials in .env file\n";
    echo "  2. Verify MySQL/MariaDB is running\n";
    echo "  3. Check database user has proper permissions\n";
    echo "  4. Verify database exists\n";
    echo "  5. Check firewall rules if using remote database\n";
    exit(1);
    
} catch (Exception $e) {
    echo "✗ Unexpected error!\n\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
