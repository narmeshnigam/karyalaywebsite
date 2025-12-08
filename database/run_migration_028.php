<?php
/**
 * Run Migration 028: Add cover_image and is_featured to case_studies table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    $sql = file_get_contents(__DIR__ . '/migrations/028_add_featured_to_case_studies.sql');
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            $db->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\nMigration 028 completed successfully!\n";
    echo "Added cover_image and is_featured columns to case_studies table.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
