<?php
/**
 * Run Migration 025 - Add Featured to Solutions
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    $sql = file_get_contents(__DIR__ . '/migrations/025_add_featured_to_solutions.sql');
    $db->exec($sql);
    
    echo "Migration 025 completed successfully!\n";
    echo "Added featured_on_homepage column to solutions table.\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
