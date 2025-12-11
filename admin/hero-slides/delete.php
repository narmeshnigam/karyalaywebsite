<?php
/**
 * Admin Delete Hero Slide
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Models\HeroSlide;

startSecureSession();
require_admin();
require_permission('hero_slides.manage');

$heroSlideModel = new HeroSlide();

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: ' . get_app_base_url() . '/admin/hero-slides.php');
    exit;
}

$slide = $heroSlideModel->getById($id);
if (!$slide) {
    $_SESSION['admin_error'] = 'Slide not found.';
    header('Location: ' . get_app_base_url() . '/admin/hero-slides.php');
    exit;
}

$result = $heroSlideModel->delete($id);
if ($result) {
    $_SESSION['admin_success'] = 'Hero slide deleted successfully!';
} else {
    $_SESSION['admin_error'] = 'Failed to delete hero slide.';
}

header('Location: ' . get_app_base_url() . '/admin/hero-slides.php');
exit;
