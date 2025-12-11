<?php
/**
 * Admin Delete Case Study Handler
 * Handles deletion of case studies
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\ContentService;

// Start secure session
startSecureSession();

// Require admin authentication and case_studies.manage permission
require_admin();
require_permission('case_studies.manage');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_message'] = 'Invalid request method.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/admin/case-studies.php');
    exit;
}

// Initialize services
$contentService = new ContentService();

// Validate CSRF token
if (!validateCsrfToken()) {
    $_SESSION['flash_message'] = 'Invalid security token. Please try again.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/admin/case-studies.php');
    exit;
}

// Get case study ID
$case_study_id = $_POST['id'] ?? '';

if (empty($case_study_id)) {
    $_SESSION['flash_message'] = 'Case study ID is required.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/admin/case-studies.php');
    exit;
}

// Attempt to delete the case study
try {
    $result = $contentService->delete('case_study', $case_study_id);
    
    if ($result) {
        $_SESSION['flash_message'] = 'Case study deleted successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to delete case study. It may not exist.';
        $_SESSION['flash_type'] = 'danger';
    }
} catch (Exception $e) {
    error_log("Case study deletion error: " . $e->getMessage());
    $_SESSION['flash_message'] = 'An error occurred while deleting the case study.';
    $_SESSION['flash_type'] = 'danger';
}

// Redirect back to case studies list
header('Location: ' . get_app_base_url() . '/admin/case-studies.php');
exit;
