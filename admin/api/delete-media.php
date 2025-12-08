<?php
/**
 * Media Delete API Endpoint
 * Handles AJAX media deletion
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../classes/Database/Connection.php';
require_once __DIR__ . '/../../classes/Models/MediaAsset.php';
require_once __DIR__ . '/../../classes/Services/MediaUploadService.php';

use Karyalay\Services\MediaUploadService;

// Start session
session_start();

// Set JSON response header
header('Content-Type: application/json');

// Require admin authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ADMIN') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
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

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid CSRF token'
    ]);
    exit;
}

// Check if asset ID was provided
if (!isset($_POST['asset_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'No asset ID provided'
    ]);
    exit;
}

// Initialize upload service
$uploadService = new MediaUploadService();

// Delete media asset
$result = $uploadService->deleteMediaAsset($_POST['asset_id']);

// Return result
if ($result['success']) {
    http_response_code(200);
    echo json_encode($result);
} else {
    http_response_code(400);
    echo json_encode($result);
}
