<?php
/**
 * Admin Delete Feature Handler
 * Handles feature deletion requests
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\ContentService;
use Karyalay\Middleware\CsrfMiddleware;

// Start secure session
startSecureSession();

// Require admin authentication and content.delete permission
require_admin();
require_permission('content.delete');

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
    header('Location: ' . get_app_base_url() . '/admin/features.php');
    exit;
}

// Get feature ID
$feature_id = $_POST['id'] ?? '';

if (empty($feature_id)) {
    $_SESSION['flash_message'] = 'Feature ID is required.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/admin/features.php');
    exit;
}

// Delete the feature
$result = $contentService->delete('feature', $feature_id);

if ($result) {
    $_SESSION['flash_message'] = 'Feature deleted successfully.';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash_message'] = 'Failed to delete feature.';
    $_SESSION['flash_type'] = 'danger';
}

header('Location: ' . get_app_base_url() . '/admin/features.php');
exit;
