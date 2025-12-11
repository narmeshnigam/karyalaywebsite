<?php
/**
 * Run Migration 044: Add tax fields to plans table
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    echo "Running migration 044: Add tax fields to plans table...\n";
    
    // Read and execute the migration SQL
    $sql = file_get_contents(__DIR__ . '/migrations/044_add_tax_fields_to_plans.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !str_starts_with($statement, '--')) {
            echo "Executing: " . substr($statement, 0, 80) . "...\n";
            $db->exec($statement);
        }
    }
    
    echo "\n✅ Migration 044 completed successfully!\n";
    echo "Added columns: discount_amount, net_price, tax_percent, tax_name, tax_description, tax_amount\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
