<?php
/**
 * Run Migration 049: Add file_path column to media_assets table
 * 
 * This migration adds a file_path column to store relative paths,
 * enabling proper dynamic URL construction for media assets.
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Database\Connection;

echo "Running Migration 049: Add file_path to media_assets...\n";

try {
    $db = Connection::getInstance();
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM media_assets LIKE 'file_path'");
    if ($stmt->rowCount() > 0) {
        echo "Column 'file_path' already exists. Skipping migration.\n";
        exit(0);
    }
    
    // Read and execute the migration SQL
    $sql = file_get_contents(__DIR__ . '/migrations/049_add_file_path_to_media_assets.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $db->exec($statement);
            echo "Executed: " . substr($statement, 0, 60) . "...\n";
        }
    }
    
    // Update existing records to populate file_path from url
    echo "Updating existing records with file_path...\n";
    
    $updateStmt = $db->prepare("
        UPDATE media_assets 
        SET file_path = SUBSTRING_INDEX(url, '/uploads/', -1)
        WHERE file_path IS NULL 
        AND url LIKE '%/uploads/%'
    ");
    $updateStmt->execute();
    $updated = $updateStmt->rowCount();
    
    // Prepend 'uploads/' to the extracted path
    $db->exec("
        UPDATE media_assets 
        SET file_path = CONCAT('uploads/', file_path)
        WHERE file_path IS NOT NULL 
        AND file_path NOT LIKE 'uploads/%'
    ");
    
    echo "Updated {$updated} existing records with file_path.\n";
    echo "Migration 049 completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
