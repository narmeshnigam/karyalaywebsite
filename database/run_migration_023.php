<?php
/**
 * Run Migration 023 - Create Hero Slides Table
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    // Read and execute the migration
    $sql = file_get_contents(__DIR__ . '/migrations/023_create_hero_slides_table.sql');
    
    $db->exec($sql);
    
    echo "Migration 023 completed successfully!\n";
    echo "Created hero_slides table.\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
