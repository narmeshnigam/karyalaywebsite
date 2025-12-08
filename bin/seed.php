#!/usr/bin/env php
<?php

/**
 * Database Seeder CLI Tool
 * 
 * Usage:
 *   php bin/seed.php    - Seed database with sample data
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;
use Karyalay\Database\Seeder;

// Load environment variables from .env if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!getenv($name)) {
            putenv("$name=$value");
        }
    }
}

try {
    $pdo = Connection::getInstance();
    $seeder = new Seeder($pdo);
    
    echo "Starting database seeding...\n\n";
    $seeder->runAll();
    
    echo "\n✓ Database seeded successfully!\n";
    echo "\nSample credentials:\n";
    echo "  Admin:    admin@karyalay.com / admin123\n";
    echo "  Customer: customer@example.com / customer123\n";
    echo "  Support:  support@karyalay.com / support123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
