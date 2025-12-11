<?php
/**
 * Run Migration 048: Create user_roles table for multiple roles per user
 * 
 * This migration:
 * 1. Creates the user_roles table for many-to-many user-role relationships
 * 2. Updates the users.role ENUM to include new roles
 * 3. Migrates existing users to the user_roles table
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

echo "Running migration 048: Create user_roles table...\n\n";

try {
    $db = Connection::getInstance();
    
    // Read the migration SQL
    $sql = file_get_contents(__DIR__ . '/migrations/048_create_user_roles_table.sql');
    
    // Split by semicolon but be careful with comments
    // Remove SQL comments first
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    // Split by semicolon and filter empty statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strlen($stmt) > 5;
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            $preview = substr(preg_replace('/\s+/', ' ', $statement), 0, 60);
            echo "[$index] Executing: {$preview}...\n";
            
            $db->exec($statement);
            $successCount++;
            echo "    ✓ Success\n";
        } catch (PDOException $e) {
            // Check if it's a "table already exists" or "duplicate key" error (which is OK)
            if (strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate entry') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "    ⚠ Skipped (already exists)\n";
                $successCount++;
            } else {
                echo "    ✗ Error: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n" . str_repeat('-', 50) . "\n";
    echo "Migration 048 completed!\n";
    echo "Successful statements: {$successCount}\n";
    echo "Failed statements: {$errorCount}\n";
    
    // Verify the migration
    echo "\n" . str_repeat('-', 50) . "\n";
    echo "Verifying migration...\n\n";
    
    // Check if user_roles table exists
    $stmt = $db->query("SHOW TABLES LIKE 'user_roles'");
    if ($stmt->rowCount() > 0) {
        echo "✓ user_roles table exists\n";
        
        // Count records
        $stmt = $db->query("SELECT COUNT(*) as count FROM user_roles");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "  - Records in user_roles: {$count}\n";
    } else {
        echo "✗ user_roles table NOT found\n";
    }
    
    // Check users.role ENUM values
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($column) {
        echo "✓ users.role column type: {$column['Type']}\n";
    }
    
    echo "\n✅ Migration 048 verification complete!\n";
    
} catch (PDOException $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
