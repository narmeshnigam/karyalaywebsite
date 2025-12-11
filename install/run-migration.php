<?php
/**
 * Migration Runner
 * Run a specific migration file
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die('This script should be run from command line or with ?run=1 parameter');
}

$migrationFile = $argv[1] ?? $_GET['file'] ?? null;

if (!$migrationFile) {
    die("Usage: php run-migration.php <migration-file>\nExample: php run-migration.php 033_create_faqs_table.sql\n");
}

$migrationPath = __DIR__ . '/../database/migrations/' . $migrationFile;

if (!file_exists($migrationPath)) {
    die("Migration file not found: $migrationPath\n");
}

echo "Running migration: $migrationFile\n";

$sql = file_get_contents($migrationPath);

if (!$sql) {
    die("Failed to read migration file\n");
}

try {
    $db = Connection::getInstance();
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        $db->exec($statement);
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
