<?php
/**
 * Admin Delete Why Choose Card
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Models\WhyChooseCard;

startSecureSession();
require_admin();
require_permission('why_choose.manage');

$cardModel = new WhyChooseCard();

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: ' . get_app_base_url() . '/admin/why-choose-cards.php');
    exit;
}

$card = $cardModel->getById($id);
if (!$card) {
    $_SESSION['admin_error'] = 'Card not found.';
    header('Location: ' . get_app_base_url() . '/admin/why-choose-cards.php');
    exit;
}

$result = $cardModel->delete($id);
if ($result) {
    $_SESSION['admin_success'] = 'Card deleted successfully!';
} else {
    $_SESSION['admin_error'] = 'Failed to delete card.';
}

header('Location: ' . get_app_base_url() . '/admin/why-choose-cards.php');
exit;
