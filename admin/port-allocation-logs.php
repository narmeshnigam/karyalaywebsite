<?php
/**
 * Admin Port Allocation Logs Page
 * Displays all port allocation activities with filters
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\PortAllocationLog;
use Karyalay\Models\Plan;
use Karyalay\Models\User;

// Start secure session
startSecureSession();

// Require admin authentication and ports.view permission
require_admin();
require_permission('ports.view');

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize models
$logModel = new PortAllocationLog();
$planModel = new Plan();
$userModel = new User();

// Get filters from query parameters
$action_filter = $_GET['action'] ?? '';
$plan_filter = $_GET['plan'] ?? '';
$customer_filter = $_GET['customer'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Build filters array
$filters = [];
if (!empty($action_filter)) {
    $filters['action'] = $action_filter;
}
if (!empty($plan_filter)) {
    $filters['plan_id'] = $plan_filter;
}
if (!empty($customer_filter)) {
    $filters['customer_id'] = $customer_filter;
}
if (!empty($date_from)) {
    $filters['date_from'] = $date_from;
}
if (!empty($date_to)) {
    $filters['date_to'] = $date_to;
}
if (!empty($search_query)) {
    $filters['search'] = $search_query;
}

// Get total count and logs
$total_logs = $logModel->countAllWithRelations($filters);
$total_pages = ceil($total_logs / $per_page);
$logs = $logModel->findAllWithRelations($filters, $per_page, $offset);

// Fetch all plans for filter dropdown
$allPlans = $planModel->findAll();

// Fetch all customers for filter dropdown
$allCustomers = $userModel->findAll(['role' => 'CUSTOMER'], 500, 0);

// Get distinct actions for filter dropdown
$allActions = $logModel->getDistinctActions();

// Define action labels and colors
$actionConfig = [
    'ASSIGNED' => ['label' => 'Assigned', 'color' => 'success'],
    'REASSIGNED' => ['label' => 'Reassigned', 'color' => 'info'],
    'RELEASED' => ['label' => 'Released', 'color' => 'warning'],
    'UNASSIGNED' => ['label' => 'Unassigned', 'color' => 'warning'],
    'CREATED' => ['label' => 'Created', 'color' => 'primary'],
    'DISABLED' => ['label' => 'Disabled', 'color' => 'danger'],
    'ENABLED' => ['label' => 'Enabled', 'color' => 'success'],
    'RESERVED' => ['label' => 'Reserved', 'color' => 'info'],
    'MADE_AVAILABLE' => ['label' => 'Made Available', 'color' => 'success'],
    'STATUS_CHANGED' => ['label' => 'Status Changed', 'color' => 'secondary'],
];

// Include admin header
include_admin_header('Port Allocation Logs');

// Include export button helper
require_once __DIR__ . '/../includes/export_button_helper.php';
?>

<?php render_export_button_styles(); ?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Port Allocation Logs</h1>
        <p class="admin-page-description">View all port allocation activities including purchases, admin allotments, and status changes</p>
    </div>
    <div class="admin-page-header-actions">
        <?php render_export_button(get_app_base_url() . '/admin/api/export-port-allocation-logs.php'); ?>
        <a href="<?php echo get_app_base_url(); ?>/admin/ports.php" class="btn btn-secondary">
            View Ports
        </a>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/port-allocation-logs.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by port URL, customer name, or email..."
                value="<?php echo htmlspecialchars($search_query); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="action" class="admin-filter-label">Action Type</label>
            <select id="action" name="action" class="admin-filter-select">
                <option value="">All Actions</option>
                <?php foreach ($allActions as $action): ?>
                    <option value="<?php echo htmlspecialchars($action); ?>" 
                            <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($actionConfig[$action]['label'] ?? ucwords(strtolower(str_replace('_', ' ', $action)))); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="admin-filter-group">
            <label for="plan" class="admin-filter-label">Plan</label>
            <select id="plan" name="plan" class="admin-filter-select">
                <option value="">All Plans</option>
                <?php foreach ($allPlans as $plan): ?>
                    <option value="<?php echo htmlspecialchars($plan['id']); ?>" 
                            <?php echo $plan_filter === $plan['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($plan['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="admin-filter-group">
            <label for="customer" class="admin-filter-label">Customer</label>
            <select id="customer" name="customer" class="admin-filter-select">
                <option value="">All Customers</option>
                <?php foreach ($allCustomers as $customer): ?>
                    <option value="<?php echo htmlspecialchars($customer['id']); ?>" 
                            <?php echo $customer_filter === $customer['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($customer['name'] . ' (' . $customer['email'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="admin-filter-group">
            <label for="date_from" class="admin-filter-label">Date From</label>
            <input 
                type="date" 
                id="date_from" 
                name="date_from" 
                class="admin-filter-input" 
                value="<?php echo htmlspecialchars($date_from); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="date_to" class="admin-filter-label">Date To</label>
            <input 
                type="date" 
                id="date_to" 
                name="date_to" 
                class="admin-filter-input" 
                value="<?php echo htmlspecialchars($date_to); ?>"
            >
        </div>
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/port-allocation-logs.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="admin-card">
    <?php if (empty($logs)): ?>
        <?php 
        render_empty_state(
            'No allocation logs found',
            'Port allocation activities will appear here as they occur',
            '',
            ''
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Action</th>
                        <th>Port</th>
                        <th>Customer</th>
                        <th>Plan</th>
                        <th>Performed By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo date('M j, Y', strtotime($log['timestamp'])); ?>
                                </div>
                                <div class="table-cell-secondary">
                                    <?php echo date('g:i:s A', strtotime($log['timestamp'])); ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $actionInfo = $actionConfig[$log['action']] ?? ['label' => $log['action'], 'color' => 'secondary'];
                                ?>
                                <span class="badge badge-<?php echo htmlspecialchars($actionInfo['color']); ?>">
                                    <?php echo htmlspecialchars($actionInfo['label']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($log['port_instance_url']): ?>
                                    <div class="table-cell-primary">
                                        <a href="<?php echo get_app_base_url(); ?>/admin/ports/view.php?id=<?php echo urlencode($log['port_id']); ?>" class="link-primary">
                                            <?php echo htmlspecialchars($log['port_instance_url']); ?>
                                        </a>
                                    </div>
                                    <div class="table-cell-secondary">
                                        <?php echo get_status_badge($log['port_status']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Port deleted</span>
                                    <div class="table-cell-secondary">
                                        <code class="code-inline"><?php echo htmlspecialchars(substr($log['port_id'], 0, 8)); ?>...</code>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['customer_name']): ?>
                                    <div class="table-cell-primary">
                                        <a href="<?php echo get_app_base_url(); ?>/admin/customers/view.php?id=<?php echo urlencode($log['customer_id']); ?>" class="link-primary">
                                            <?php echo htmlspecialchars($log['customer_name']); ?>
                                        </a>
                                    </div>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars($log['customer_email']); ?>
                                    </div>
                                <?php elseif ($log['customer_id']): ?>
                                    <span class="text-muted">Customer deleted</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['plan_name']): ?>
                                    <?php echo htmlspecialchars($log['plan_name']); ?>
                                <?php elseif ($log['subscription_id']): ?>
                                    <span class="text-muted">Plan deleted</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['performed_by_name']): ?>
                                    <div class="table-cell-primary">
                                        <?php echo htmlspecialchars($log['performed_by_name']); ?>
                                    </div>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars($log['performed_by_email']); ?>
                                    </div>
                                <?php elseif ($log['performed_by']): ?>
                                    <span class="text-muted">User deleted</span>
                                <?php else: ?>
                                    <span class="text-muted auto-label">Automatic</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log['notes']): ?>
                                    <div class="notes-cell" title="<?php echo htmlspecialchars($log['notes']); ?>">
                                        <?php echo htmlspecialchars(substr($log['notes'], 0, 50)); ?>
                                        <?php echo strlen($log['notes']) > 50 ? '...' : ''; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="admin-card-footer">
                <?php 
                $base_url = get_app_base_url() . '/admin/port-allocation-logs.php';
                $query_params = [];
                if (!empty($action_filter)) {
                    $query_params[] = 'action=' . urlencode($action_filter);
                }
                if (!empty($plan_filter)) {
                    $query_params[] = 'plan=' . urlencode($plan_filter);
                }
                if (!empty($customer_filter)) {
                    $query_params[] = 'customer=' . urlencode($customer_filter);
                }
                if (!empty($date_from)) {
                    $query_params[] = 'date_from=' . urlencode($date_from);
                }
                if (!empty($date_to)) {
                    $query_params[] = 'date_to=' . urlencode($date_to);
                }
                if (!empty($search_query)) {
                    $query_params[] = 'search=' . urlencode($search_query);
                }
                if (!empty($query_params)) {
                    $base_url .= '?' . implode('&', $query_params);
                }
                render_pagination($page, $total_pages, $base_url);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-card-footer">
            <p class="admin-card-footer-text">
                Showing <?php echo count($logs); ?> of <?php echo $total_logs; ?> log<?php echo $total_logs !== 1 ? 's' : ''; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-6);
    gap: var(--spacing-4);
}

.admin-page-header-content {
    flex: 1;
}

.admin-page-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.admin-page-description {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    margin: 0;
}

.admin-page-header-actions {
    display: flex;
    gap: var(--spacing-3);
}

.admin-filters-section {
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-6);
}

.admin-filters-form {
    display: flex;
    gap: var(--spacing-4);
    align-items: flex-end;
    flex-wrap: wrap;
}

.admin-filter-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
    flex: 1;
    min-width: 160px;
}

.admin-filter-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
}

.admin-filter-input,
.admin-filter-select {
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
}

.admin-filter-input:focus,
.admin-filter-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.admin-filter-actions {
    display: flex;
    gap: var(--spacing-2);
}

.table-cell-primary {
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
}

.table-cell-secondary {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-top: var(--spacing-1);
}

.code-inline {
    background-color: var(--color-gray-100);
    padding: 2px 6px;
    border-radius: var(--radius-sm);
    font-family: 'Courier New', monospace;
    font-size: var(--font-size-sm);
    color: var(--color-gray-800);
}

.text-muted {
    color: var(--color-gray-500);
    font-style: italic;
}

.auto-label {
    background-color: var(--color-gray-100);
    padding: 2px 8px;
    border-radius: var(--radius-sm);
    font-size: var(--font-size-sm);
}

.link-primary {
    color: var(--color-primary);
    text-decoration: none;
}

.link-primary:hover {
    text-decoration: underline;
}

.notes-cell {
    max-width: 200px;
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
}

.admin-card-footer-text {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
}

.admin-table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.admin-table {
    min-width: 100%;
    table-layout: auto;
}

.admin-table th,
.admin-table td {
    white-space: nowrap;
    padding: var(--spacing-3);
}

.admin-table td {
    white-space: normal;
    word-wrap: break-word;
    word-break: break-word;
}

.badge-primary {
    background-color: var(--color-primary);
    color: white;
}

@media (max-width: 1200px) {
    .admin-table th,
    .admin-table td {
        font-size: var(--font-size-sm);
        padding: var(--spacing-2);
    }
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    
    .admin-page-header-actions {
        width: 100%;
    }
    
    .admin-page-header-actions .btn {
        flex: 1;
    }
    
    .admin-filters-form {
        flex-direction: column;
    }
    
    .admin-filter-group {
        width: 100%;
    }
}
</style>

<?php include_admin_footer(); ?>
