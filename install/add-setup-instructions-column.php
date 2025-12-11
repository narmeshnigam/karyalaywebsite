<?php
/**
 * Add setup_instructions column to ports table
 * Run this script once to add the new column
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

echo "Adding setup_instructions column to ports table...\n";

try {
    $db = Connection::getInstance();
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM ports LIKE 'setup_instructions'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "Column 'setup_instructions' already exists. No changes needed.\n";
    } else {
        // Add the column
        $sql = "ALTER TABLE ports ADD COLUMN setup_instructions TEXT NULL AFTER notes";
        $db->exec($sql);
        echo "Successfully added 'setup_instructions' column to ports table.\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
