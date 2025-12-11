<?php
/**
 * Admin Delete Plan Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\PlanService;

// Start secure session
startSecureSession();

// Require admin authentication and plans.delete permission
require_admin();
require_permission('plans.delete');

// Initialize services
$planService = new PlanService();

// Get plan ID from query parameter
$planId = $_GET['id'] ?? '';

if (empty($planId)) {
    $_SESSION['admin_error'] = 'Plan ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/plans.php');
    exit;
}

// Fetch plan data
$plan = $planService->read($planId);

if (!$plan) {
    $_SESSION['admin_error'] = 'Plan not found.';
    header('Location: ' . get_app_base_url() . '/admin/plans.php');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        $_SESSION['admin_error'] = 'Invalid security token. Please try again.';
        header('Location: ' . get_app_base_url() . '/admin/plans.php');
        exit;
    }

    // Check if plan has active subscriptions
    $db = \Karyalay\Database\Connection::getInstance();
    $stmt = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE plan_id = :plan_id AND status = 'ACTIVE'");
    $stmt->execute([':plan_id' => $planId]);
    $activeSubscriptions = $stmt->fetchColumn();

    if ($activeSubscriptions > 0) {
        $_SESSION['admin_error'] = "Cannot delete plan with {$activeSubscriptions} active subscription(s). Please deactivate the plan instead.";
        header('Location: ' . get_app_base_url() . '/admin/plans.php');
        exit;
    }

    // Delete plan
    $result = $planService->delete($planId);

    if ($result) {
        $_SESSION['admin_success'] = 'Plan deleted successfully!';
    } else {
        $_SESSION['admin_error'] = 'Failed to delete plan. Please try again.';
    }

    header('Location: ' . get_app_base_url() . '/admin/plans.php');
    exit;
}

// If GET request, show error message and redirect to plans list
$_SESSION['admin_error'] = 'Delete action requires POST request. Please use the delete button from the edit page.';
header('Location: ' . get_app_base_url() . '/admin/plans.php');
exit;
