<?php
/**
 * Run Migration 035: Restructure Ports Table
 * 
 * This script will:
 * 1. Drop plan_id foreign key constraint
 * 2. Drop idx_plan_id index
 * 3. Drop plan_id column
 * 
 * Usage:
 *   php run_migration_035.php          - Run the migration
 *   php run_migration_035.php rollback - Rollback the migration
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

$action = $argv[1] ?? 'migrate';

try {
    $db = Connection::getInstance();
    
    if ($action === 'rollback') {
        runRollback($db);
    } else {
        runMigration($db);
    }
    
} catch (PDOException $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

function runMigration(PDO $db): void
{
    echo "Starting migration 035: Restructure Ports Table\n";
    echo "================================================\n\n";
    
    // Check if migration already completed (plan_id column doesn't exist)
    $stmt = $db->query("SHOW COLUMNS FROM ports LIKE 'plan_id'");
    if ($stmt->rowCount() === 0) {
        echo "✓ Migration already completed - 'plan_id' column doesn't exist\n";
        exit(0);
    }

    
    // Step 1: Drop foreign key constraint on plan_id
    echo "Step 1: Dropping foreign key constraint on 'plan_id'...\n";
    try {
        // Try the default constraint name first
        $db->exec("ALTER TABLE ports DROP FOREIGN KEY ports_ibfk_1");
        echo "✓ Foreign key constraint dropped\n\n";
    } catch (PDOException $e) {
        // Constraint might have a different name or already dropped
        echo "  (Foreign key constraint not found or already dropped)\n\n";
    }
    
    // Step 2: Drop idx_plan_id index
    echo "Step 2: Dropping 'idx_plan_id' index...\n";
    try {
        $db->exec("DROP INDEX idx_plan_id ON ports");
        echo "✓ Index dropped\n\n";
    } catch (PDOException $e) {
        echo "  (Index not found or already dropped)\n\n";
    }
    
    // Step 3: Drop plan_id column
    echo "Step 3: Dropping 'plan_id' column...\n";
    try {
        $stmt = $db->query("SHOW COLUMNS FROM ports LIKE 'plan_id'");
        if ($stmt->rowCount() > 0) {
            $db->exec("ALTER TABLE ports DROP COLUMN plan_id");
            echo "✓ 'plan_id' column dropped\n\n";
        } else {
            echo "  (Column already dropped, skipping)\n\n";
        }
    } catch (PDOException $e) {
        throw new PDOException("Failed to drop plan_id column: " . $e->getMessage());
    }
    
    echo "================================================\n";
    echo "✓ Migration 035 completed successfully!\n";
    echo "================================================\n";
}

function runRollback(PDO $db): void
{
    echo "Rolling back migration 035: Restructure Ports Table\n";
    echo "====================================================\n\n";
    
    echo "⚠ WARNING: Rollback requires a valid plan_id for existing ports.\n";
    echo "  This rollback will set plan_id to NULL for existing ports.\n";
    echo "  You may need to manually update plan_id values after rollback.\n\n";
    
    // Step 1: Add plan_id column back
    echo "Step 1: Adding 'plan_id' column...\n";
    try {
        $stmt = $db->query("SHOW COLUMNS FROM ports LIKE 'plan_id'");
        if ($stmt->rowCount() === 0) {
            $db->exec("ALTER TABLE ports ADD COLUMN plan_id CHAR(36) NULL AFTER db_password");
            echo "✓ 'plan_id' column added\n\n";
        } else {
            echo "  (Column already exists, skipping)\n\n";
        }
    } catch (PDOException $e) {
        throw new PDOException("Failed to add plan_id column: " . $e->getMessage());
    }
    
    // Step 2: Add idx_plan_id index
    echo "Step 2: Adding 'idx_plan_id' index...\n";
    try {
        $db->exec("CREATE INDEX idx_plan_id ON ports(plan_id)");
        echo "✓ Index created\n\n";
    } catch (PDOException $e) {
        echo "  (Index already exists or failed to create)\n\n";
    }
    
    // Step 3: Add foreign key constraint (only if plan_id has valid values)
    echo "Step 3: Foreign key constraint...\n";
    echo "  ⚠ Skipping foreign key - plan_id values need to be set first\n";
    echo "  Run this manually after setting plan_id values:\n";
    echo "  ALTER TABLE ports ADD FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT;\n\n";
    
    echo "====================================================\n";
    echo "✓ Rollback 035 completed!\n";
    echo "⚠ Remember to set plan_id values and add foreign key constraint.\n";
    echo "====================================================\n";
}
