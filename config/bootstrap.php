<?php

/**
 * Application Bootstrap
 * Initializes core services and error handling
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment configuration
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// Set timezone
date_default_timezone_set('UTC');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    $config = require __DIR__ . '/app.php';
    
    session_set_cookie_params([
        'lifetime' => $config['session']['lifetime'] * 60,
        'path' => '/',
        'domain' => '',
        'secure' => $config['session']['cookie_secure'],
        'httponly' => $config['session']['cookie_httponly'],
        'samesite' => $config['session']['cookie_samesite'],
    ]);
    
    session_name($config['session']['cookie_name']);
    session_start();
}

// Initialize error handling and monitoring
require_once __DIR__ . '/../includes/error_handler.php';

// Set user context for error tracking if logged in
if (isset($_SESSION['user_id'])) {
    $errorTracker = \Karyalay\Services\ErrorTrackingService::getInstance();
    $errorTracker->setUser([
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
    ]);
}

// Check installation status and redirect to wizard if not installed
$installationService = new \Karyalay\Services\InstallationService();

// Get the current request URI
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Determine if we're in the install directory
$isInstallPath = (
    strpos($requestUri, '/install/') !== false ||
    strpos($requestUri, '/install') !== false ||
    strpos($scriptName, '/install/') !== false
);

// If not installed and not already on install path, redirect to installation wizard
if (!$installationService->isInstalled() && !$isInstallPath) {
    // Determine the base path for the redirect
    $basePath = dirname($scriptName);
    if ($basePath === '/' || $basePath === '\\') {
        $basePath = '';
    }
    
    // Redirect to installation wizard
    header('Location: ' . $basePath . '/install/');
    exit;
}

