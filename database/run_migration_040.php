<?php
/**
 * Run Migration 040: Update orders payment columns
 * 
 * This migration:
 * - Renames payment_gateway_id to pg_order_id
 * - Adds pg_payment_id column
 * - Adds index for pg_payment_id
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    echo "Running migration 040: Update orders payment columns...\n";
    
    // Read and execute migration
    $sql = file_get_contents(__DIR__ . '/migrations/040_update_orders_payment_columns.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $db->exec($statement);
            echo "  ✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "✓ Migration 040 completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
