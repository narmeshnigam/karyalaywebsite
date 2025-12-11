<?php
/**
 * API endpoint for lead notes (GET, POST, DELETE)
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

$leadModel = new Lead();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get notes for a lead
        if (empty($_GET['lead_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Lead ID is required']);
            exit;
        }
        
        $notes = $leadModel->getNotes($_GET['lead_id']);
        echo json_encode(['success' => true, 'notes' => $notes]);
        break;

    case 'POST':
        // Add a new note
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        if (empty($input['lead_id']) || empty($input['note'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Lead ID and note are required']);
            exit;
        }

        $note = $leadModel->addNote(
            $input['lead_id'],
            $_SESSION['user_id'],
            trim($input['note'])
        );

        if ($note) {
            echo json_encode([
                'success' => true,
                'message' => 'Note added successfully',
                'note' => $note
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to add note']);
        }
        break;

    case 'DELETE':
        // Delete a note
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['note_id']) || empty($input['lead_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Note ID and Lead ID are required']);
            exit;
        }

        $result = $leadModel->deleteNote($input['note_id'], $input['lead_id']);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to delete note']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
