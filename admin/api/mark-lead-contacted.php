<?php
/**
 * API endpoint to mark lead as contacted
 * Requirements: 12.4
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../classes/Database/Connection.php';
require_once __DIR__ . '/../../classes/Models/Lead.php';

use Karyalay\Models\Lead;

// Start session
session_start();

// Require admin authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'ADMIN') {
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

// Check if lead ID is provided
if (!isset($_POST['lead_id']) || empty($_POST['lead_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Lead ID is required']);
    exit;
}

$leadId = $_POST['lead_id'];
$notes = $_POST['notes'] ?? null;

// Initialize model
$leadModel = new Lead();

// Mark lead as contacted
$result = $leadModel->markAsContacted($leadId, $notes);

if ($result) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Lead marked as contacted successfully'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to mark lead as contacted'
    ]);
}
