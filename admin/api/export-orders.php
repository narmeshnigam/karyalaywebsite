<?php
/**
 * Export Orders to CSV
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Services\ExcelExportService;

// Start secure session
startSecureSession();

// Require admin authentication and orders.view permission
require_admin();
require_permission('orders.view');

// Get filters from query parameters
$filters = [];

if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

if (!empty($_GET['payment_method'])) {
    $filters['payment_method'] = $_GET['payment_method'];
}

if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Export orders
$exportService = new ExcelExportService();
$exportService->exportOrders($filters);
