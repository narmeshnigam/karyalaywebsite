<?php
/**
 * Run Migration 045: Enhance port_allocation_logs table
 * 
 * This migration:
 * 1. Expands action ENUM to include more status types
 * 2. Adds notes column for additional context
 * 3. Makes subscription_id and customer_id nullable
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

echo "Running Migration 045: Enhance port_allocation_logs table\n";
echo "=========================================================\n\n";

try {
    $db = Connection::getInstance();
    
    // Read migration file
    $migrationFile = __DIR__ . '/migrations/045_enhance_port_allocation_logs.sql';
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
    
    echo "Found " . count($statements) . " SQL statements to execute.\n\n";
    
    foreach ($statements as $index => $statement) {
        // Skip comment-only statements
        $cleanStatement = preg_replace('/--.*$/m', '', $statement);
        $cleanStatement = trim($cleanStatement);
        
        if (empty($cleanStatement)) {
            continue;
        }
        
        echo "Executing statement " . ($index + 1) . "...\n";
        echo substr($cleanStatement, 0, 100) . (strlen($cleanStatement) > 100 ? '...' : '') . "\n";
        
        try {
            $db->exec($cleanStatement);
            echo "✓ Success\n\n";
        } catch (PDOException $e) {
            // Check if it's a "column already exists" or similar non-critical error
            if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Can\'t DROP') !== false) {
                echo "⚠ Skipped (already applied): " . $e->getMessage() . "\n\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "=========================================================\n";
    echo "Migration 045 completed successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
