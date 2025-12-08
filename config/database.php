<?php

/**
 * Database Configuration
 * 
 * This file contains database connection settings.
 * Update these values according to your environment.
 */

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'karyalay_portal',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'unix_socket' => getenv('DB_UNIX_SOCKET') ?: '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        // Enable persistent connections for connection pooling
        // This reuses existing connections instead of creating new ones
        PDO::ATTR_PERSISTENT => getenv('DB_PERSISTENT') === 'true',
        // Set timeout for connection attempts
        PDO::ATTR_TIMEOUT => 5,
    ],
];
