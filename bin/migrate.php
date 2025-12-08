#!/usr/bin/env php
<?php

/**
 * Database Migration CLI Tool
 * 
 * Usage:
 *   php bin/migrate.php          - Run all pending migrations
 *   php bin/migrate.php reset    - Reset database (WARNING: deletes all data)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;
use Karyalay\Database\Migration;

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
    $migrationsPath = __DIR__ . '/../database/migrations';
    $migration = new Migration($pdo, $migrationsPath);
    
    $command = $argv[1] ?? 'run';
    
    switch ($command) {
        case 'reset':
            echo "WARNING: This will delete all data!\n";
            echo "Are you sure? (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            
            if (trim($line) !== 'yes') {
                echo "Aborted.\n";
                exit(0);
            }
            
            echo "Resetting database...\n";
            $migration->reset();
            echo "Database reset complete.\n";
            echo "\nRunning migrations...\n";
            $results = $migration->runAll();
            break;
            
        case 'run':
        default:
            echo "Running migrations...\n";
            $results = $migration->runAll();
            break;
    }
    
    // Display results
    foreach ($results as $file => $status) {
        $statusColor = $status === 'success' ? "\033[32m" : 
                      ($status === 'skipped' ? "\033[33m" : "\033[31m");
        $resetColor = "\033[0m";
        
        echo sprintf(
            "  %s%-50s%s %s\n",
            $statusColor,
            $file,
            $resetColor,
            strtoupper($status)
        );
    }
    
    echo "\nMigration complete!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
