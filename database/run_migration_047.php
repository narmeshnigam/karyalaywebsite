<?php
/**
 * Run Migration 047: Fix port_allocation_logs table
 * 
 * This migration ensures the port_allocation_logs table can handle all scenarios:
 * 1. Automatic port allocation during purchase
 * 2. Admin status changes
 * 3. Admin reassignments
 * 4. Port creation logging
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

echo "Running Migration 047: Fix port_allocation_logs table\n";
echo "======================================================\n\n";

try {
    $db = Connection::getInstance();
    
    // Step 1: Check current table structure
    echo "Checking current table structure...\n";
    $stmt = $db->query("DESCRIBE port_allocation_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasNotes = false;
    $subscriptionNullable = false;
    $customerNullable = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'notes') {
            $hasNotes = true;
        }
        if ($col['Field'] === 'subscription_id' && $col['Null'] === 'YES') {
            $subscriptionNullable = true;
        }
        if ($col['Field'] === 'customer_id' && $col['Null'] === 'YES') {
            $customerNullable = true;
        }
    }
    
    echo "Current state:\n";
    echo "  - notes column exists: " . ($hasNotes ? 'Yes' : 'No') . "\n";
    echo "  - subscription_id nullable: " . ($subscriptionNullable ? 'Yes' : 'No') . "\n";
    echo "  - customer_id nullable: " . ($customerNullable ? 'Yes' : 'No') . "\n\n";
    
    // Step 2: Change action column to VARCHAR to accept any action type
    echo "Step 1: Modifying action column to VARCHAR(50)...\n";
    try {
        $db->exec("ALTER TABLE port_allocation_logs MODIFY COLUMN action VARCHAR(50) NOT NULL");
        echo "✓ Action column modified\n\n";
    } catch (PDOException $e) {
        echo "⚠ Could not modify action column: " . $e->getMessage() . "\n\n";
    }
    
    // Step 3: Add notes column if missing
    if (!$hasNotes) {
        echo "Step 2: Adding notes column...\n";
        try {
            $db->exec("ALTER TABLE port_allocation_logs ADD COLUMN notes TEXT NULL");
            echo "✓ Notes column added\n\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠ Notes column already exists\n\n";
            } else {
                echo "⚠ Could not add notes column: " . $e->getMessage() . "\n\n";
            }
        }
    } else {
        echo "Step 2: Notes column already exists, skipping...\n\n";
    }
    
    // Step 4: Make subscription_id nullable
    if (!$subscriptionNullable) {
        echo "Step 3: Making subscription_id nullable...\n";
        
        // First, try to drop foreign key if it exists
        try {
            $db->exec("ALTER TABLE port_allocation_logs DROP FOREIGN KEY port_allocation_logs_ibfk_2");
            echo "  - Dropped foreign key port_allocation_logs_ibfk_2\n";
        } catch (PDOException $e) {
            // Try alternative constraint name
            try {
                $db->exec("ALTER TABLE port_allocation_logs DROP FOREIGN KEY fk_pal_subscription");
                echo "  - Dropped foreign key fk_pal_subscription\n";
            } catch (PDOException $e2) {
                echo "  - No foreign key to drop (or already dropped)\n";
            }
        }
        
        try {
            $db->exec("ALTER TABLE port_allocation_logs MODIFY COLUMN subscription_id CHAR(36) NULL");
            echo "✓ subscription_id is now nullable\n\n";
        } catch (PDOException $e) {
            echo "⚠ Could not modify subscription_id: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "Step 3: subscription_id already nullable, skipping...\n\n";
    }
    
    // Step 5: Make customer_id nullable
    if (!$customerNullable) {
        echo "Step 4: Making customer_id nullable...\n";
        
        // First, try to drop foreign key if it exists
        try {
            $db->exec("ALTER TABLE port_allocation_logs DROP FOREIGN KEY port_allocation_logs_ibfk_3");
            echo "  - Dropped foreign key port_allocation_logs_ibfk_3\n";
        } catch (PDOException $e) {
            // Try alternative constraint name
            try {
                $db->exec("ALTER TABLE port_allocation_logs DROP FOREIGN KEY fk_pal_customer");
                echo "  - Dropped foreign key fk_pal_customer\n";
            } catch (PDOException $e2) {
                echo "  - No foreign key to drop (or already dropped)\n";
            }
        }
        
        try {
            $db->exec("ALTER TABLE port_allocation_logs MODIFY COLUMN customer_id CHAR(36) NULL");
            echo "✓ customer_id is now nullable\n\n";
        } catch (PDOException $e) {
            echo "⚠ Could not modify customer_id: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "Step 4: customer_id already nullable, skipping...\n\n";
    }
    
    // Step 6: Verify final structure
    echo "Verifying final table structure...\n";
    $stmt = $db->query("DESCRIBE port_allocation_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nFinal table structure:\n";
    echo str_repeat('-', 80) . "\n";
    printf("%-20s %-30s %-10s %-10s\n", "Field", "Type", "Null", "Key");
    echo str_repeat('-', 80) . "\n";
    
    foreach ($columns as $col) {
        printf("%-20s %-30s %-10s %-10s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key']
        );
    }
    
    echo str_repeat('-', 80) . "\n";
    echo "\n======================================================\n";
    echo "Migration 047 completed!\n";
    echo "\nYou can now test port allocation logging.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
