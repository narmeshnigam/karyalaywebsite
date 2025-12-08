<?php

namespace Karyalay\Database;

use PDO;
use PDOException;

/**
 * Database Migration Class
 * 
 * Handles running SQL migration scripts to set up database schema
 */
class Migration
{
    private PDO $pdo;
    private string $migrationsPath;

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     * @param string $migrationsPath Path to migration files
     */
    public function __construct(PDO $pdo, string $migrationsPath)
    {
        $this->pdo = $pdo;
        $this->migrationsPath = rtrim($migrationsPath, '/');
    }

    /**
     * Run all pending migrations
     * 
     * @return array Results of migration execution
     */
    public function runAll(): array
    {
        $this->createMigrationsTable();
        
        $files = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();
        $results = [];

        foreach ($files as $file) {
            $filename = basename($file);
            
            if (in_array($filename, $executed)) {
                $results[$filename] = 'skipped';
                continue;
            }

            try {
                $this->runMigration($file);
                $this->recordMigration($filename);
                $results[$filename] = 'success';
            } catch (PDOException $e) {
                $results[$filename] = 'failed: ' . $e->getMessage();
                break; // Stop on first failure
            }
        }

        return $results;
    }

    /**
     * Create migrations tracking table if it doesn't exist
     * 
     * @return void
     */
    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->pdo->exec($sql);
    }

    /**
     * Get list of migration files
     * 
     * @return array
     */
    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.sql');
        sort($files);
        return $files;
    }

    /**
     * Get list of already executed migrations
     * 
     * @return array
     */
    private function getExecutedMigrations(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Run a single migration file
     * 
     * @param string $file Path to migration file
     * @return void
     * @throws PDOException
     */
    private function runMigration(string $file): void
    {
        $sql = file_get_contents($file);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt)
        );

        // DDL statements (CREATE, ALTER, DROP) cannot be rolled back in MySQL
        // Execute each statement individually without transaction
        foreach ($statements as $statement) {
            $this->pdo->exec($statement);
        }
    }

    /**
     * Record a migration as executed
     * 
     * @param string $filename Migration filename
     * @return void
     */
    private function recordMigration(string $filename): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$filename]);
    }

    /**
     * Reset all migrations (drop all tables and clear migrations table)
     * WARNING: This will delete all data!
     * 
     * @return void
     */
    public function reset(): void
    {
        // Get all tables
        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Disable foreign key checks
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        // Drop all tables
        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
        }

        // Re-enable foreign key checks
        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}
