<?php
/**
 * API endpoint to get lead details
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

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Lead ID is required']);
    exit;
}

$leadId = $_GET['id'];

// Initialize model
$leadModel = new Lead();

// Get lead details
$lead = $leadModel->findById($leadId);

if (!$lead) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Lead not found']);
    exit;
}

// Return lead details
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'lead' => $lead
]);
