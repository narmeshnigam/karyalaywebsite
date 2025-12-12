<?php

/**
 * Database Configuration
 * 
 * This file contains database connection settings.
 * The active DB_* variables are automatically populated by the dual-environment
 * credential resolution system in bootstrap.php based on the detected environment.
 * 
 * @see config/bootstrap.php for dual-environment credential resolution
 * @see classes/Services/EnvironmentConfigManager.php for credential management
 */

/**
 * Helper function to get environment variable with fallback
 * Checks both getenv() and $_ENV for compatibility
 * 
 * @param string $key Environment variable key
 * @param mixed $default Default value if not found
 * @return mixed Environment variable value or default
 */
function getEnvValue(string $key, $default = null) {
    // First check getenv() (set by putenv())
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    
    // Then check $_ENV superglobal
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    
    return $default;
}

return [
    // Database connection settings (auto-resolved from DB_LOCAL_* or DB_LIVE_* based on environment)
    'host' => getEnvValue('DB_HOST', 'localhost'),
    'port' => getEnvValue('DB_PORT', '3306'),
    'database' => getEnvValue('DB_NAME', 'karyalay_portal'),
    'username' => getEnvValue('DB_USER', 'root'),
    'password' => getEnvValue('DB_PASS', ''),
    'unix_socket' => getEnvValue('DB_UNIX_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        // Enable persistent connections for connection pooling
        // This reuses existing connections instead of creating new ones
        PDO::ATTR_PERSISTENT => getEnvValue('DB_PERSISTENT') === 'true',
        // Set timeout for connection attempts
        PDO::ATTR_TIMEOUT => 5,
    ],
];
