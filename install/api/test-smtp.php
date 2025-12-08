<?php
/**
 * Installation Wizard - Test SMTP Connection API Endpoint
 * 
 * This endpoint tests SMTP configuration by attempting to send a test email.
 * 
 * Requirements: 4.2
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required classes
require_once __DIR__ . '/../../vendor/autoload.php';

use Karyalay\Services\InstallationService;
use Karyalay\Services\CsrfService;

// Set JSON response header
header('Content-Type: application/json');

// Initialize services
$installationService = new InstallationService();
$csrfService = new CsrfService();

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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON input'
    ]);
    exit;
}

// Validate required fields
$requiredFields = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_address', 'smtp_from_name'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || trim($input[$field]) === '') {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: ' . implode(', ', $missingFields)
    ]);
    exit;
}

// Validate port number
$port = filter_var($input['smtp_port'], FILTER_VALIDATE_INT);
if ($port === false || $port < 1 || $port > 65535) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid port number'
    ]);
    exit;
}

// Validate email address
if (!filter_var($input['smtp_from_address'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid from address'
    ]);
    exit;
}

// Validate encryption type
$validEncryption = ['tls', 'ssl', 'none'];
if (!in_array($input['smtp_encryption'], $validEncryption)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid encryption type'
    ]);
    exit;
}

// Prepare SMTP configuration
$smtpConfig = [
    'smtp_host' => trim($input['smtp_host']),
    'smtp_port' => (int) $input['smtp_port'],
    'smtp_username' => trim($input['smtp_username']),
    'smtp_password' => $input['smtp_password'],
    'smtp_encryption' => $input['smtp_encryption'],
    'smtp_from_address' => trim($input['smtp_from_address']),
    'smtp_from_name' => trim($input['smtp_from_name'])
];

// Test SMTP connection
$result = $installationService->testSmtpConnection($smtpConfig);

if ($result['success']) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'SMTP connection successful! Test email sent to ' . $smtpConfig['smtp_from_address']
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $result['error']
    ]);
}
