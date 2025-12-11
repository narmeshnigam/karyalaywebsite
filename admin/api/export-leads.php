<?php
/**
 * Export Leads to CSV
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Services\ExcelExportService;

// Start secure session
startSecureSession();

// Require admin authentication and leads.view permission
require_admin();
require_permission('leads.view');

// Get filters from query parameters
$filters = [];

if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

if (!empty($_GET['source'])) {
    $filters['source'] = $_GET['source'];
}

if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Export leads
$exportService = new ExcelExportService();
$exportService->exportLeads($filters);
