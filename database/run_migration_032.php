<?php

/**
 * Run Migration 032: Update features table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    echo "Running migration 032: Update features table...\n";
    
    $sql = file_get_contents(__DIR__ . '/migrations/032_update_features_table.sql');
    
    $db->exec($sql);
    
    echo "✓ Migration 032 completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Migration 032 failed: " . $e->getMessage() . "\n";
    exit(1);
}
