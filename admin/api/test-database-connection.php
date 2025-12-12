<?php
/**
 * Test Database Connection API
 * Tests database connection with provided credentials
 * 
 * Requirements: 4.3 - Validate and test connection before saving
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Services\DatabaseValidationService;

header('Content-Type: application/json');

// Start session and check admin authentication
startSecureSession();

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// Get credentials from POST
$credentials = [
    'host' => trim($_POST['host'] ?? ''),
    'port' => trim($_POST['port'] ?? '3306'),
    'database' => trim($_POST['database'] ?? ''),
    'username' => trim($_POST['username'] ?? ''),
    'password' => $_POST['password'] ?? '',
    'unix_socket' => trim($_POST['unix_socket'] ?? '')
];

// Validate required fields
if (empty($credentials['host'])) {
    echo json_encode(['success' => false, 'message' => 'Host is required', 'field' => 'host']);
    exit;
}

if (empty($credentials['database'])) {
    echo json_encode(['success' => false, 'message' => 'Database name is required', 'field' => 'database']);
    exit;
}

if (empty($credentials['username'])) {
    echo json_encode(['success' => false, 'message' => 'Username is required', 'field' => 'username']);
    exit;
}

// Test connection
$dbValidator = new DatabaseValidationService();
$result = $dbValidator->testConnection($credentials);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'message' => $result['server_info']['message'],
        'server_info' => $result['server_info']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $result['error_message'],
        'error_type' => $result['error_type'],
        'field' => $result['field'],
        'troubleshooting' => $result['troubleshooting']
    ]);
}
