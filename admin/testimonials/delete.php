<?php
/**
 * Admin Delete Testimonial Handler
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Models\Testimonial;

startSecureSession();
require_admin();
require_permission('testimonials.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . get_app_base_url() . '/admin/testimonials.php');
    exit;
}

if (!validateCsrfToken()) {
    $_SESSION['admin_error'] = 'Invalid security token.';
    header('Location: ' . get_app_base_url() . '/admin/testimonials.php');
    exit;
}

$testimonial_id = $_POST['id'] ?? '';

if (empty($testimonial_id)) {
    $_SESSION['admin_error'] = 'Testimonial ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/testimonials.php');
    exit;
}

$testimonialModel = new Testimonial();
$result = $testimonialModel->delete($testimonial_id);

if ($result) {
    $_SESSION['admin_success'] = 'Testimonial deleted successfully!';
} else {
    $_SESSION['admin_error'] = 'Failed to delete testimonial.';
}

header('Location: ' . get_app_base_url() . '/admin/testimonials.php');
exit;
