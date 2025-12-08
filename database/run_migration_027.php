<?php
/**
 * Run Migration 027: Create testimonials table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    $sql = file_get_contents(__DIR__ . '/migrations/027_create_testimonials_table.sql');
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            $db->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\nMigration 027 completed successfully!\n";
    echo "Created testimonials table.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
