<?php

/**
 * Run Migration 031: Add benefits to solutions
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    echo "Running migration 031: Add benefits to solutions...\n";
    
    $sql = file_get_contents(__DIR__ . '/migrations/031_add_benefits_to_solutions.sql');
    
    $db->exec($sql);
    
    echo "✓ Migration 031 completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Migration 031 failed: " . $e->getMessage() . "\n";
    exit(1);
}
