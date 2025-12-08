<?php
/**
 * Run Migration 030: Create leads table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    $sql = file_get_contents(__DIR__ . '/migrations/030_create_leads_table.sql');
    
    $db->exec($sql);
    
    echo "\nMigration 030 completed successfully!\n";
    echo "Created leads table.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
