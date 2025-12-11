<?php
/**
 * Export Customers to CSV
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Services\ExcelExportService;

// Start secure session
startSecureSession();

// Require admin authentication and customers.view permission
require_admin();
require_permission('customers.view');

// Get filters from query parameters
$filters = [];

if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Export customers
$exportService = new ExcelExportService();
$exportService->exportCustomers($filters);
