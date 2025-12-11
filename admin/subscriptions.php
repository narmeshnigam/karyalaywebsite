<?php
/**
 * Admin Subscriptions List Page
 * Displays all subscriptions with filters
 * Requirements: 10.3
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Models\User;

// Start secure session
startSecureSession();

// Require admin authentication and subscriptions.view permission
require_admin();
require_permission('subscriptions.view');

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize models
$subscriptionModel = new Subscription();
$planModel = new Plan();
$userModel = new User();

// Get filters from query parameters
$status_filter = $_GET['status'] ?? '';
$plan_filter = $_GET['plan'] ?? '';
$customer_filter = $_GET['customer'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Fetch all plans for filter dropdown
$allPlans = $planModel->findAll();

// Build query for counting total subscriptions
$count_sql = "SELECT COUNT(*) FROM subscriptions WHERE 1=1";
$count_params = [];

if (!empty($status_filter) && in_array($status_filter, ['ACTIVE', 'EXPIRED', 'CANCELLED', 'PENDING_ALLOCATION'])) {
    $count_sql .= " AND status = :status";
    $count_params[':status'] = $status_filter;
}

if (!empty($plan_filter)) {
    $count_sql .= " AND plan_id = :plan_id";
    $count_params[':plan_id'] = $plan_filter;
}

if (!empty($customer_filter)) {
    $count_sql .= " AND customer_id = :customer_id";
    $count_params[':customer_id'] = $customer_filter;
}

if (!empty($date_from)) {
    $count_sql .= " AND end_date >= :date_from";
    $count_params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $count_sql .= " AND end_date <= :date_to";
    $count_params[':date_to'] = $date_to;
}

if (!empty($search_query)) {
    $count_sql .= " AND id IN (SELECT s.id FROM subscriptions s 
                    LEFT JOIN users u ON s.customer_id = u.id 
                    WHERE u.name LIKE :search OR u.email LIKE :search OR s.id LIKE :search)";
    $count_params[':search'] = '%' . $search_query . '%';
}

try {
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_subscriptions = $count_stmt->fetchColumn();
    $total_pages = ceil($total_subscriptions / $per_page);
} catch (PDOException $e) {
    error_log("Subscriptions count error: " . $e->getMessage());
    $total_subscriptions = 0;
    $total_pages = 0;
}

// Build query for fetching subscriptions with joins
$sql = "SELECT s.*, 
        p.name as plan_name,
        COALESCE(NULLIF(p.discounted_price, 0), p.mrp) as plan_price,
        p.currency as plan_currency,
        u.name as customer_name,
        u.email as customer_email,
        port.instance_url as port_url,
        port.db_name as port_db_name
        FROM subscriptions s
        LEFT JOIN plans p ON s.plan_id = p.id
        LEFT JOIN users u ON s.customer_id = u.id
        LEFT JOIN ports port ON s.assigned_port_id = port.id
        WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND s.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($plan_filter)) {
    $sql .= " AND s.plan_id = :plan_id";
    $params[':plan_id'] = $plan_filter;
}

if (!empty($customer_filter)) {
    $sql .= " AND s.customer_id = :customer_id";
    $params[':customer_id'] = $customer_filter;
}

if (!empty($date_from)) {
    $sql .= " AND s.end_date >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND s.end_date <= :date_to";
    $params[':date_to'] = $date_to;
}

if (!empty($search_query)) {
    $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR s.id LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";

try {
    error_log("=== SUBSCRIPTIONS DEBUG ===");
    error_log("SQL: " . $sql);
    error_log("Params: " . json_encode($params));
    error_log("Limit: " . $per_page . ", Offset: " . $offset);
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Fetched " . count($subscriptions) . " subscriptions");
    error_log("Total subscriptions count: " . $total_subscriptions);
    error_log("==========================");
} catch (PDOException $e) {
    error_log("Subscriptions list error: " . $e->getMessage());
    error_log("SQL: " . $sql);
    $subscriptions = [];
}

// Include admin header
include_admin_header('Subscriptions');

// Include export button helper
require_once __DIR__ . '/../includes/export_button_helper.php';
?>

<?php render_export_button_styles(); ?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Subscription Management</h1>
        <p class="admin-page-description">View and manage customer subscriptions</p>
    </div>
    <div class="admin-page-header-actions">
        <?php render_export_button(get_app_base_url() . '/admin/api/export-subscriptions.php'); ?>
        <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions/new.php" class="btn btn-primary">
            Create Subscription
        </a>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/subscriptions.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by customer name, email, or ID..."
                value="<?php echo htmlspecialchars($search_query); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="status" class="admin-filter-label">Status</label>
            <select id="status" name="status" class="admin-filter-select">
                <option value="">All Statuses</option>
                <option value="ACTIVE" <?php echo $status_filter === 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                <option value="EXPIRED" <?php echo $status_filter === 'EXPIRED' ? 'selected' : ''; ?>>Expired</option>
                <option value="CANCELLED" <?php echo $status_filter === 'CANCELLED' ? 'selected' : ''; ?>>Cancelled</option>
                <option value="PENDING_ALLOCATION" <?php echo $status_filter === 'PENDING_ALLOCATION' ? 'selected' : ''; ?>>Pending Allocation</option>
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
        
        <?php if (!empty($customer_filter)): ?>
        <?php 
        // Get customer name for display
        $selectedCustomer = $userModel->findById($customer_filter);
        ?>
        <div class="admin-filter-group">
            <label for="customer" class="admin-filter-label">Customer</label>
            <div class="selected-customer-filter">
                <span class="selected-customer-name">
                    <?php echo htmlspecialchars($selectedCustomer['name'] ?? 'Unknown Customer'); ?>
                </span>
                <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php" class="remove-customer-filter" title="Remove customer filter">×</a>
            </div>
            <input type="hidden" name="customer" value="<?php echo htmlspecialchars($customer_filter); ?>">
        </div>
        <?php endif; ?>
        
        <div class="admin-filter-group">
            <label for="date_from" class="admin-filter-label">Expiry From</label>
            <input 
                type="date" 
                id="date_from" 
                name="date_from" 
                class="admin-filter-input" 
                value="<?php echo htmlspecialchars($date_from); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="date_to" class="admin-filter-label">Expiry To</label>
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
            <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Subscriptions Table -->
<div class="admin-card">
    <?php if (empty($subscriptions)): ?>
        <?php 
        render_empty_state(
            'No subscriptions found',
            'No subscriptions match your current filters',
            '',
            ''
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Subscription ID</th>
                        <th>Customer</th>
                        <th>Plan</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Assigned Port</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscriptions as $subscription): ?>
                        <tr>
                            <td>
                                <code class="code-inline"><?php echo htmlspecialchars(substr($subscription['id'], 0, 8)); ?></code>
                            </td>
                            <td>
                                <?php if ($subscription['customer_name']): ?>
                                    <a href="<?php echo get_app_base_url(); ?>/admin/customers/view.php?id=<?php echo urlencode($subscription['customer_id']); ?>" class="table-link">
                                        <div class="table-cell-primary">
                                            <?php echo htmlspecialchars($subscription['customer_name']); ?>
                                        </div>
                                        <div class="table-cell-secondary">
                                            <?php echo htmlspecialchars($subscription['customer_email']); ?>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($subscription['plan_name']): ?>
                                    <a href="<?php echo get_app_base_url(); ?>/admin/plans/view.php?id=<?php echo urlencode($subscription['plan_id']); ?>" class="table-link">
                                        <div class="table-cell-primary">
                                            <?php echo htmlspecialchars($subscription['plan_name']); ?>
                                        </div>
                                        <?php if ($subscription['plan_price']): ?>
                                            <div class="table-cell-secondary">
                                                <?php echo format_price($subscription['plan_price']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No plan</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($subscription['start_date'])); ?></td>
                            <td>
                                <?php 
                                $end_date = strtotime($subscription['end_date']);
                                $now = time();
                                $is_expired = $end_date < $now;
                                $is_expiring_soon = !$is_expired && ($end_date - $now) < (7 * 24 * 60 * 60); // 7 days
                                ?>
                                <span class="<?php echo $is_expired ? 'text-danger' : ($is_expiring_soon ? 'text-warning' : ''); ?>">
                                    <?php echo date('M j, Y', $end_date); ?>
                                </span>
                                <?php if ($is_expiring_soon && !$is_expired): ?>
                                    <div class="table-cell-secondary">
                                        Expires in <?php echo ceil(($end_date - $now) / (24 * 60 * 60)); ?> days
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_status_badge($subscription['status']); ?></td>
                            <td>
                                <?php if ($subscription['port_url']): ?>
                                    <a href="<?php echo get_app_base_url(); ?>/admin/ports/view.php?id=<?php echo urlencode($subscription['assigned_port_id']); ?>" class="table-link">
                                        <div class="table-cell-primary">
                                            <?php echo htmlspecialchars($subscription['port_url']); ?>
                                        </div>
                                        <?php if ($subscription['port_db_name']): ?>
                                            <div class="table-cell-secondary">
                                                DB: <?php echo htmlspecialchars($subscription['port_db_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No port assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo get_app_base_url(); ?>/admin/subscriptions/view.php?id=<?php echo urlencode($subscription['id']); ?>" class="btn btn-text btn-sm">
                                    View Details
                                </a>
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
                $base_url = get_app_base_url() . '/admin/subscriptions.php';
                $query_params = [];
                if (!empty($status_filter)) {
                    $query_params[] = 'status=' . urlencode($status_filter);
                }
                if (!empty($plan_filter)) {
                    $query_params[] = 'plan=' . urlencode($plan_filter);
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
                if (!empty($customer_filter)) {
                    $query_params[] = 'customer=' . urlencode($customer_filter);
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
                Showing <?php echo count($subscriptions); ?> of <?php echo $total_subscriptions; ?> subscription<?php echo $total_subscriptions !== 1 ? 's' : ''; ?>
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

.admin-page-header-actions {
    display: flex;
    gap: var(--spacing-3);
}

.btn-icon {
    margin-right: var(--spacing-1);
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
    min-width: 180px;
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

/* Table Layout */
.admin-table-container {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    table-layout: fixed;
}

.admin-table th,
.admin-table td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}

/* Column widths */
.admin-table th:nth-child(1),
.admin-table td:nth-child(1) { width: 100px; } /* Subscription ID */

.admin-table th:nth-child(2),
.admin-table td:nth-child(2) { width: 160px; } /* Customer */

.admin-table th:nth-child(3),
.admin-table td:nth-child(3) { width: 120px; } /* Plan */

.admin-table th:nth-child(4),
.admin-table td:nth-child(4) { width: 90px; } /* Start Date */

.admin-table th:nth-child(5),
.admin-table td:nth-child(5) { width: 100px; white-space: normal; } /* End Date */

.admin-table th:nth-child(6),
.admin-table td:nth-child(6) { width: 100px; } /* Status */

.admin-table th:nth-child(7),
.admin-table td:nth-child(7) { width: 160px; } /* Assigned Port */

.admin-table th:nth-child(8),
.admin-table td:nth-child(8) { width: 100px; } /* Actions */

.table-cell-primary {
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.table-cell-secondary {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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

.text-danger {
    color: var(--color-red-600);
    font-weight: var(--font-weight-semibold);
}

.text-warning {
    color: var(--color-orange-600);
    font-weight: var(--font-weight-semibold);
}

.table-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

.table-link:hover .table-cell-primary {
    color: var(--color-primary);
}

.table-link:hover {
    background-color: transparent;
}

.admin-card-footer-text {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
}

.selected-customer-filter {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--spacing-2) var(--spacing-3);
    background-color: var(--color-blue-50);
    border: 1px solid var(--color-blue-200);
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
}

.selected-customer-name {
    color: var(--color-blue-800);
    font-weight: var(--font-weight-semibold);
}

.remove-customer-filter {
    color: var(--color-blue-600);
    text-decoration: none;
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-bold);
    margin-left: var(--spacing-2);
    padding: 0 var(--spacing-1);
}

.remove-customer-filter:hover {
    color: var(--color-blue-800);
    background-color: var(--color-blue-100);
    border-radius: var(--radius-sm);
}

@media (max-width: 1200px) {
    .admin-table {
        table-layout: auto;
    }
    
    .admin-table th,
    .admin-table td {
        white-space: normal;
    }
}

@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
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
