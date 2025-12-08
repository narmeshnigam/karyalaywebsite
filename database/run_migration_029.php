<?php
/**
 * Run Migration 029: Add is_featured to blog_posts table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Karyalay\Database\Connection;

try {
    $db = Connection::getInstance();
    
    $sql = file_get_contents(__DIR__ . '/migrations/029_add_featured_to_blog_posts.sql');
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && strpos($statement, '--') !== 0) {
            $db->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\nMigration 029 completed successfully!\n";
    echo "Added is_featured column to blog_posts table.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
