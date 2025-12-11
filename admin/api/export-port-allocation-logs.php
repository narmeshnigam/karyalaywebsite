<?php
/**
 * Export Port Allocation Logs API
 * Exports port allocation logs to Excel format
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Models\PortAllocationLog;

// Start secure session
startSecureSession();

// Require admin authentication and ports.view permission
require_admin();
require_permission('ports.view');

// Initialize model
$logModel = new PortAllocationLog();

// Get filters from query parameters
$filters = [];
if (!empty($_GET['action'])) {
    $filters['action'] = $_GET['action'];
}
if (!empty($_GET['plan'])) {
    $filters['plan_id'] = $_GET['plan'];
}
if (!empty($_GET['customer'])) {
    $filters['customer_id'] = $_GET['customer'];
}
if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// Get all logs with relations (no pagination for export)
$logs = $logModel->findAllWithRelations($filters, 10000, 0);

// Define action labels
$actionLabels = [
    'ASSIGNED' => 'Assigned',
    'REASSIGNED' => 'Reassigned',
    'RELEASED' => 'Released',
    'UNASSIGNED' => 'Unassigned',
    'CREATED' => 'Created',
    'DISABLED' => 'Disabled',
    'ENABLED' => 'Enabled',
    'RESERVED' => 'Reserved',
    'MADE_AVAILABLE' => 'Made Available',
    'STATUS_CHANGED' => 'Status Changed',
];

// Set headers for Excel download
$filename = 'port_allocation_logs_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
fputcsv($output, [
    'Timestamp',
    'Action',
    'Port URL',
    'Port Status',
    'Customer Name',
    'Customer Email',
    'Plan',
    'Subscription ID',
    'Performed By',
    'Performer Email',
    'Notes',
    'Log ID',
    'Port ID',
    'Customer ID'
]);

// Write data rows
foreach ($logs as $log) {
    fputcsv($output, [
        $log['timestamp'],
        $actionLabels[$log['action']] ?? $log['action'],
        $log['port_instance_url'] ?? 'Deleted',
        $log['port_status'] ?? 'N/A',
        $log['customer_name'] ?? ($log['customer_id'] ? 'Deleted' : ''),
        $log['customer_email'] ?? '',
        $log['plan_name'] ?? ($log['subscription_id'] ? 'Deleted' : ''),
        $log['subscription_id'] ?? '',
        $log['performed_by_name'] ?? ($log['performed_by'] ? 'Deleted' : 'Automatic'),
        $log['performed_by_email'] ?? '',
        $log['notes'] ?? '',
        $log['id'],
        $log['port_id'],
        $log['customer_id'] ?? ''
    ]);
}

fclose($output);
exit;
