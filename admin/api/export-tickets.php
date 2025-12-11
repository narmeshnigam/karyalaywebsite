<?php
/**
 * Export Tickets to CSV
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Services\ExcelExportService;

// Start secure session
startSecureSession();

// Require admin authentication and tickets.view permission
require_admin();
require_permission('tickets.view');

// Get filters from query parameters
$filters = [];

if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

if (!empty($_GET['priority'])) {
    $filters['priority'] = $_GET['priority'];
}

if (!empty($_GET['category'])) {
    $filters['category'] = $_GET['category'];
}

if (!empty($_GET['assigned_to'])) {
    $filters['assigned_to'] = $_GET['assigned_to'];
}

if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Export tickets
$exportService = new ExcelExportService();
$exportService->exportTickets($filters);
