<?php
/**
 * Run Migration 024 - Create Why Choose Cards Table
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    $sql = file_get_contents(__DIR__ . '/migrations/024_create_why_choose_cards_table.sql');
    $db->exec($sql);
    
    echo "Migration 024 completed successfully!\n";
    echo "Created why_choose_cards table.\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
