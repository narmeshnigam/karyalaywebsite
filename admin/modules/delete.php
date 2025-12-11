<?php
/**
 * Admin Delete Module Handler
 * Handles module deletion requests
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\ContentService;
use Karyalay\Middleware\CsrfMiddleware;

// Start secure session
startSecureSession();

// Require admin authentication and solutions.manage permission
require_admin();
require_permission('solutions.manage');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Initialize services
$contentService = new ContentService();
$csrfMiddleware = new CsrfMiddleware();

// Validate CSRF token
if (!validateCsrfToken()) {
    $_SESSION['flash_message'] = 'Invalid security token. Please try again.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/admin/modules.php');
    exit;
}

// Get module ID
$module_id = $_POST['id'] ?? '';

if (empty($module_id)) {
    $_SESSION['flash_message'] = 'Module ID is required.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/admin/modules.php');
    exit;
}

// Delete the module
$result = $contentService->delete('module', $module_id);

if ($result) {
    $_SESSION['flash_message'] = 'Module deleted successfully.';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Failed to delete module.';
    $_SESSION['flash_type'] = 'danger';
}

header('Location: ' . get_app_base_url() . '/admin/modules.php');
exit;
