<?php
/**
 * Run Migration 036 - Update Plans Table
 * 
 * Adds: number_of_users, allowed_storage_gb, mrp, discounted_price, features_html
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

echo "Running Migration 036: Update Plans Table\n";
echo "==========================================\n\n";

try {
    $db = Connection::getInstance();
    
    // Read migration file
    $migrationFile = __DIR__ . '/migrations/036_update_plans_table.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strpos($stmt, '--') !== 0;
        }
    );
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        // Skip comment-only lines
        $lines = explode("\n", $statement);
        $hasCode = false;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (!empty($trimmed) && strpos($trimmed, '--') !== 0) {
                $hasCode = true;
                break;
            }
        }
        
        if (!$hasCode) continue;
        
        echo "Executing: " . substr(trim($statement), 0, 60) . "...\n";
        
        try {
            $db->exec($statement);
            echo "  ✓ Success\n";
        } catch (PDOException $e) {
            // Check if it's a duplicate column error (column already exists)
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "  ⚠ Column already exists, skipping\n";
            } elseif (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "  ⚠ Index already exists, skipping\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n==========================================\n";
    echo "Migration 036 completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
