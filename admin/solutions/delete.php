<?php
/**
 * Admin Delete Solution Handler
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\ContentService;

startSecureSession();
require_admin();
require_permission('solutions.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

$contentService = new ContentService();

if (!validateCsrfToken()) {
    $_SESSION['admin_error'] = 'Invalid security token. Please try again.';
    header('Location: ' . get_app_base_url() . '/admin/solutions.php');
    exit;
}

$solution_id = $_POST['id'] ?? '';

if (empty($solution_id)) {
    $_SESSION['admin_error'] = 'Solution ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/solutions.php');
    exit;
}

$result = $contentService->delete('solution', $solution_id);

if ($result) {
    $_SESSION['admin_success'] = 'Solution deleted successfully.';
} else {
    $_SESSION['admin_error'] = 'Failed to delete solution.';
}

header('Location: ' . get_app_base_url() . '/admin/solutions.php');
exit;
