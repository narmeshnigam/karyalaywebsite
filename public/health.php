<?php

/**
 * Health Check Endpoint
 * Used by load balancers and monitoring systems to check application health
 */

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

// Check database connection
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../classes/Database/Connection.php';
    
    $db = \Karyalay\Database\Connection::getInstance();
    $db->query('SELECT 1');
    
    $health['checks']['database'] = [
        'status' => 'healthy',
        'message' => 'Database connection successful'
    ];
} catch (Exception $e) {
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = [
        'status' => 'unhealthy',
        'message' => 'Database connection failed'
    ];
}

// Check storage directory is writable
$storageDir = __DIR__ . '/../storage/cache';
if (is_writable($storageDir)) {
    $health['checks']['storage'] = [
        'status' => 'healthy',
        'message' => 'Storage directory is writable'
    ];
} else {
    $health['status'] = 'unhealthy';
    $health['checks']['storage'] = [
        'status' => 'unhealthy',
        'message' => 'Storage directory is not writable'
    ];
}

// Check uploads directory is writable
$uploadsDir = __DIR__ . '/../uploads';
if (is_writable($uploadsDir)) {
    $health['checks']['uploads'] = [
        'status' => 'healthy',
        'message' => 'Uploads directory is writable'
    ];
} else {
    $health['status'] = 'unhealthy';
    $health['checks']['uploads'] = [
        'status' => 'unhealthy',
        'message' => 'Uploads directory is not writable'
    ];
}

// Check PHP version
$requiredPhpVersion = '8.0.0';
if (version_compare(PHP_VERSION, $requiredPhpVersion, '>=')) {
    $health['checks']['php_version'] = [
        'status' => 'healthy',
        'message' => 'PHP version ' . PHP_VERSION . ' meets requirements',
        'version' => PHP_VERSION
    ];
} else {
    $health['status'] = 'unhealthy';
    $health['checks']['php_version'] = [
        'status' => 'unhealthy',
        'message' => 'PHP version ' . PHP_VERSION . ' does not meet requirements (>= ' . $requiredPhpVersion . ')',
        'version' => PHP_VERSION
    ];
}

// Check required PHP extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'bcmath'];
$missingExtensions = [];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

if (empty($missingExtensions)) {
    $health['checks']['php_extensions'] = [
        'status' => 'healthy',
        'message' => 'All required PHP extensions are loaded'
    ];
} else {
    $health['status'] = 'unhealthy';
    $health['checks']['php_extensions'] = [
        'status' => 'unhealthy',
        'message' => 'Missing required PHP extensions: ' . implode(', ', $missingExtensions),
        'missing' => $missingExtensions
    ];
}

// Set HTTP status code
http_response_code($health['status'] === 'healthy' ? 200 : 503);

// Output health check result
echo json_encode($health, JSON_PRETTY_PRINT);
