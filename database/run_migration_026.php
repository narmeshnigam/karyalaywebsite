<?php
/**
 * Run Migration 026: Add icon_image to solutions table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    // Read and execute the migration SQL
    $sql = file_get_contents(__DIR__ . '/migrations/026_add_icon_image_to_solutions.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            $db->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\nMigration 026 completed successfully!\n";
    echo "Added icon_image column to solutions table.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
