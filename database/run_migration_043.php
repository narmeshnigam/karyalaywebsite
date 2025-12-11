<?php
/**
 * Run Migration 043: Add invoice_id to orders table
 * 
 * This migration:
 * - Adds invoice_id column to orders table
 * - Adds index for invoice_id lookups
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    echo "Running migration 043: Add invoice_id to orders...\n";
    
    // Read and execute migration
    $sql = file_get_contents(__DIR__ . '/migrations/043_add_invoice_id_to_orders.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $db->exec($statement);
            echo "  ✓ Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "✓ Migration 043 completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
