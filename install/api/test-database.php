<?php
/**
 * Installation Wizard - Test Database Connection API
 * 
 * This endpoint tests database connection with provided credentials.
 * Returns JSON response with success status and detailed error information.
 * 
 * Requirements: 2.2, 6.3, 6.4, 6.5
 */

// Set JSON response header
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required classes
require_once __DIR__ . '/../../vendor/autoload.php';

use Karyalay\Services\InstallationService;
use Karyalay\Services\CsrfService;
use Karyalay\Services\DatabaseValidationService;

// Initialize services
$installationService = new InstallationService();
$csrfService = new CsrfService();

// Check if already installed
if ($installationService->isInstalled()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Installation already completed'
    ]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Validate CSRF token
if (!$csrfService->validateRequest()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token'
    ]);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// If JSON decode failed, try to get from POST
if ($data === null) {
    $data = $_POST;
}

// Validate required fields
$requiredFields = ['database', 'username'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit;
}

// Validate host or unix_socket
if (empty($data['unix_socket']) && empty($data['host'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Either host or unix_socket must be provided.'
    ]);
    exit;
}

// Prepare credentials
$credentials = [
    'host' => trim($data['host'] ?? 'localhost'),
    'port' => !empty($data['port']) ? (int) $data['port'] : 3306,
    'database' => trim($data['database']),
    'username' => trim($data['username']),
    'password' => $data['password'] ?? '',
    'unix_socket' => trim($data['unix_socket'] ?? '')
];

// Test database connection with detailed error handling
try {
    $validationService = new DatabaseValidationService();
    $result = $validationService->testConnection($credentials);
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful! You can proceed to the next step.',
            'server_info' => $result['server_info']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['error_message'],
            'error_type' => $result['error_type'],
            'error_details' => $result['error_details'],
            'troubleshooting' => $result['troubleshooting'],
            'field' => $result['field']
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred while testing the connection.',
        'error_type' => 'unknown',
        'error_details' => $e->getMessage(),
        'troubleshooting' => [
            'Please try again',
            'If the problem persists, contact support'
        ],
        'field' => null
    ]);
}
