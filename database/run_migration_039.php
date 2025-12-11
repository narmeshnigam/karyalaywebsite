<?php
/**
 * Run Migration 039: Drop price column from plans table
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    echo "Running migration 039: Drop price column from plans table...\n";
    
    // Read and execute migration
    $sql = file_get_contents(__DIR__ . '/migrations/039_drop_price_column_from_plans.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        $db->exec($statement);
    }
    
    echo "Migration 039 completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration 039 failed: " . $e->getMessage() . "\n";
    exit(1);
}
