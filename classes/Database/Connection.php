<?php

namespace Karyalay\Database;

use PDO;
use PDOException;

/**
 * Database Connection Class
 * 
 * Manages database connections using PDO with singleton pattern
 * to ensure only one connection instance exists.
 */
class Connection
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get database connection instance
     * 
     * @return PDO
     * @throws PDOException
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }

        return self::$instance;
    }

    /**
     * Set database configuration
     * 
     * @param array $config Database configuration array
     * @return void
     */
    public static function setConfig(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Establish database connection
     * 
     * @return void
     * @throws PDOException
     */
    private static function connect(): void
    {
        if (empty(self::$config)) {
            self::$config = require __DIR__ . '/../../config/database.php';
        }

        // Use unix_socket if available, otherwise use host:port
        if (!empty(self::$config['unix_socket']) && file_exists(self::$config['unix_socket'])) {
            $dsn = sprintf(
                'mysql:unix_socket=%s;dbname=%s;charset=%s',
                self::$config['unix_socket'],
                self::$config['database'],
                self::$config['charset']
            );
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['port'],
                self::$config['database'],
                self::$config['charset']
            );
        }

        try {
            self::$instance = new PDO(
                $dsn,
                self::$config['username'],
                self::$config['password'],
                self::$config['options']
            );
        } catch (PDOException $e) {
            throw new PDOException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }

    /**
     * Close database connection
     * 
     * @return void
     */
    public static function close(): void
    {
        self::$instance = null;
    }

    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Check if currently in a transaction
     * 
     * @return bool
     */
    public static function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }
}
