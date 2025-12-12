<?php
/**
 * Installation Wizard - Complete Installation API Endpoint
 * 
 * This endpoint finalizes the installation by creating the lock file,
 * clearing wizard session data, and logging the installation completion.
 * 
 * Requirements: 1.4, 7.3, 10.1
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required classes
require_once __DIR__ . '/../../vendor/autoload.php';

use Karyalay\Services\InstallationService;
use Karyalay\Services\CsrfService;
use Karyalay\Services\LoggerService;
use Karyalay\Services\UrlService;

// Set JSON response header
header('Content-Type: application/json');

// Initialize services
$installationService = new InstallationService();
$csrfService = new CsrfService();
$logger = LoggerService::getInstance();

// Check if already installed
if ($installationService->isInstalled()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Installation already completed'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

// Validate CSRF token
if (!$csrfService->validateRequest()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid security token'
    ]);
    exit;
}

// Verify all required steps are completed
$progress = $installationService->getProgress();

$requiredSteps = [
    'database_configured',
    'migrations_run',
    'admin_created'
];

$missingSteps = [];
foreach ($requiredSteps as $step) {
    if (empty($progress[$step])) {
        $missingSteps[] = $step;
    }
}

if (!empty($missingSteps)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Cannot complete installation. Missing required steps: ' . implode(', ', $missingSteps)
    ]);
    exit;
}

// Complete installation
try {
    $result = $installationService->completeInstallation();
    
    if (!$result['success']) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error']
        ]);
        exit;
    }
    
    // Use UrlService to resolve the correct redirect URL
    // Requirements: 7.3 - Use resolved base URL for admin dashboard redirect
    $urlService = new UrlService();
    $redirectUrl = $urlService->getAdminDashboardUrl();
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Installation completed successfully!',
        'redirect' => $redirectUrl
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while completing installation: ' . $e->getMessage()
    ]);
    
    // Log the error
    $logger->error('Installation completion failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
