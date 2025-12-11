<?php
/**
 * API endpoint to update lead status
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';

use Karyalay\Models\Lead;

header('Content-Type: application/json');

// Start secure session
startSecureSession();

// Require admin authentication
if (!isAuthenticated() || !isAdmin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Validate required fields
if (empty($input['lead_id']) || empty($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Lead ID and status are required']);
    exit;
}

$leadId = $input['lead_id'];
$status = strtoupper($input['status']);

// Validate status
$validStatuses = ['NEW', 'CONTACTED', 'QUALIFIED', 'CONVERTED', 'LOST'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

$leadModel = new Lead();

// Verify lead exists
$lead = $leadModel->findById($leadId);
if (!$lead) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Lead not found']);
    exit;
}

// Update status
$result = $leadModel->updateStatus($leadId, $status);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Lead status updated successfully',
        'status' => $status
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update lead status']);
}
