<?php
/**
 * Run Migration 046: Drop assigned_customer_id from ports table
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    echo "Running migration 046: Drop assigned_customer_id from ports table\n";
    echo "================================================================\n\n";
    
    // Check if column exists
    $checkColumn = $db->query("SHOW COLUMNS FROM ports LIKE 'assigned_customer_id'");
    if ($checkColumn->rowCount() === 0) {
        echo "Column 'assigned_customer_id' does not exist. Migration already applied.\n";
        exit(0);
    }
    
    // Drop foreign key if exists (try different possible names)
    $foreignKeyNames = ['ports_ibfk_2', 'fk_ports_assigned_customer_id', 'ports_assigned_customer_id_foreign'];
    
    foreach ($foreignKeyNames as $fkName) {
        try {
            $db->exec("ALTER TABLE ports DROP FOREIGN KEY {$fkName}");
            echo "Dropped foreign key: {$fkName}\n";
            break;
        } catch (PDOException $e) {
            // Foreign key might not exist with this name, continue
        }
    }
    
    // Drop the column
    $db->exec("ALTER TABLE ports DROP COLUMN assigned_customer_id");
    echo "Dropped column: assigned_customer_id\n";
    
    echo "\n✓ Migration 046 completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
