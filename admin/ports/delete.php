<?php
/**
 * Admin Delete Port Page
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../classes/Services/PortService.php';
require_once __DIR__ . '/../../classes/Services/CsrfService.php';

use Karyalay\Services\PortService;
use Karyalay\Services\CsrfService;

// Start session
session_start();

// Require admin authentication and ports.delete permission
require_admin();
require_permission('ports.delete');

// Initialize services
$portService = new PortService();
$csrfService = new CsrfService();

// Get port ID from query parameter
$portId = $_GET['id'] ?? '';

if (empty($portId)) {
    $_SESSION['admin_error'] = 'Port ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/ports.php');
    exit;
}

// Validate CSRF token if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['admin_error'] = 'Invalid security token.';
        header('Location: ' . get_app_base_url() . '/admin/ports.php');
        exit;
    }

    // Delete the port
    $result = $portService->deletePort($portId);

    if ($result['success']) {
        $_SESSION['admin_success'] = 'Port deleted successfully!';
    } else {
        $_SESSION['admin_error'] = $result['error'] ?? 'Failed to delete port.';
    }

    header('Location: ' . get_app_base_url() . '/admin/ports.php');
    exit;
}

// If GET request, redirect to edit page (delete should be POST only)
header('Location: ' . get_app_base_url() . '/admin/ports/edit.php?id=' . urlencode($portId));
exit;
