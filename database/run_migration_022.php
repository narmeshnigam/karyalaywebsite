<?php
/**
 * Run Migration 022: Rename Modules to Solutions
 * 
 * This script will:
 * 1. Rename modules table to solutions
 * 2. Update indexes
 * 3. Rename columns in case_studies and features tables
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    echo "Starting migration 022: Rename Modules to Solutions\n";
    echo "=================================================\n\n";
    
    // Check if modules table exists
    $stmt = $db->query("SHOW TABLES LIKE 'modules'");
    if ($stmt->rowCount() === 0) {
        echo "✓ Migration already completed - 'modules' table doesn't exist\n";
        echo "✓ 'solutions' table already exists\n";
        exit(0);
    }
    
    // Step 1: Rename modules table to solutions
    echo "Step 1: Renaming 'modules' table to 'solutions'...\n";
    $db->exec("RENAME TABLE modules TO solutions");
    echo "✓ Table renamed successfully\n\n";
    
    // Step 2: Update index
    echo "Step 2: Updating indexes...\n";
    try {
        $db->exec("ALTER TABLE solutions DROP INDEX idx_modules_status_order");
        echo "✓ Dropped old index\n";
    } catch (PDOException $e) {
        echo "  (Old index didn't exist, skipping)\n";
    }
    
    $db->exec("CREATE INDEX idx_solutions_status_order ON solutions(status, display_order)");
    echo "✓ Created new index\n\n";
    
    // Step 3: Rename modules_used in case_studies
    echo "Step 3: Renaming 'modules_used' to 'solutions_used' in case_studies...\n";
    try {
        $db->exec("ALTER TABLE case_studies CHANGE COLUMN modules_used solutions_used JSON");
        echo "✓ Column renamed successfully\n\n";
    } catch (PDOException $e) {
        echo "  (Column already renamed or doesn't exist)\n\n";
    }
    
    // Step 4: Rename related_modules in features
    echo "Step 4: Renaming 'related_modules' to 'related_solutions' in features...\n";
    try {
        $db->exec("ALTER TABLE features CHANGE COLUMN related_modules related_solutions JSON");
        echo "✓ Column renamed successfully\n\n";
    } catch (PDOException $e) {
        echo "  (Column already renamed or doesn't exist)\n\n";
    }
    
    echo "=================================================\n";
    echo "✓ Migration 022 completed successfully!\n";
    echo "=================================================\n";
    
} catch (PDOException $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
