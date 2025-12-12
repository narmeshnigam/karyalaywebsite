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
            
            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = str_replace(['\\"', '\\\\'], ['"', '\\'], $matches[1]);
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// =============================================================================
// DUAL-ENVIRONMENT DATABASE CREDENTIAL RESOLUTION
// =============================================================================
// Automatically detect the environment (localhost vs production) and resolve
// the appropriate database credentials from DB_LOCAL_* or DB_LIVE_* prefixes.
// This enables seamless "git push and go live" workflow.

// Track if we have valid credentials for later fallback check
$hasValidDatabaseCredentials = false;

// Only resolve credentials if the system is installed and .env exists
$envConfigManager = null;
if (file_exists(__DIR__ . '/../.env') && file_exists(__DIR__ . '/.installed')) {
    try {
        $envConfigManager = new \Karyalay\Services\EnvironmentConfigManager();
        $resolved = $envConfigManager->resolveCredentials();
        
        if ($resolved['credentials'] !== null) {
            // Set active DB_ environment variables based on resolved credentials
            $envConfigManager->setActiveCredentials($resolved['credentials']);
            $hasValidDatabaseCredentials = true;
        }
    } catch (\Exception $e) {
        // Log error but don't break the application
        error_log('Bootstrap: Failed to resolve dual-environment credentials - ' . $e->getMessage());
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

// =============================================================================
// FALLBACK TO INSTALLATION WIZARD IF NO VALID CREDENTIALS
// =============================================================================
// If the system is installed but no valid database credentials are available
// for the current environment, redirect to the installation wizard to reconfigure.
// This handles the case where credentials are missing or invalid after deployment.
// (Requirement 2.5: WHEN neither Credential_Set is available THEN redirect to wizard)

if ($installationService->isInstalled() && !$isInstallPath && !$hasValidDatabaseCredentials) {
    // Check if we truly have no valid credentials by attempting to resolve again
    // This double-check ensures we don't redirect unnecessarily
    $configManager = new \Karyalay\Services\EnvironmentConfigManager();
    $credentialCheck = $configManager->resolveCredentials();
    
    if ($credentialCheck['credentials'] === null) {
        // Log the issue for debugging
        error_log('Bootstrap: No valid database credentials available. Redirecting to installation wizard.');
        error_log('Bootstrap: Detected environment: ' . ($credentialCheck['detected_environment'] ?? 'unknown'));
        error_log('Bootstrap: Local credentials available: ' . ($credentialCheck['local_available'] ? 'yes' : 'no'));
        error_log('Bootstrap: Live credentials available: ' . ($credentialCheck['live_available'] ? 'yes' : 'no'));
        
        // Determine the base path for the redirect
        $basePath = dirname($scriptName);
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        
        // Redirect to installation wizard with a query parameter indicating reconfiguration needed
        header('Location: ' . $basePath . '/install/?reconfigure=database');
        exit;
    }
}

