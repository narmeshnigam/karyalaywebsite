<?php
/**
 * Admin Orders List Page
 * Displays all transactions with filters
 * Requirements: 10.4
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

use Karyalay\Models\Order;
use Karyalay\Models\Plan;
use Karyalay\Models\User;

// Start secure session
startSecureSession();

// Require admin authentication and orders.view permission
require_admin();
require_permission('orders.view');

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize models
$orderModel = new Order();
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

// Build query for counting total orders
$count_sql = "SELECT COUNT(*) FROM orders WHERE 1=1";
$count_params = [];

if (!empty($status_filter) && in_array($status_filter, ['PENDING', 'SUCCESS', 'FAILED', 'CANCELLED'])) {
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
    $count_sql .= " AND DATE(created_at) >= :date_from";
    $count_params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $count_sql .= " AND DATE(created_at) <= :date_to";
    $count_params[':date_to'] = $date_to;
}

if (!empty($search_query)) {
    $count_sql .= " AND id IN (SELECT o.id FROM orders o 
                    LEFT JOIN users u ON o.customer_id = u.id 
                    WHERE u.name LIKE :search OR u.email LIKE :search OR o.id LIKE :search OR o.pg_order_id LIKE :search OR o.pg_payment_id LIKE :search)";
    $count_params[':search'] = '%' . $search_query . '%';
}

try {
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_orders = $count_stmt->fetchColumn();
    $total_pages = ceil($total_orders / $per_page);
} catch (PDOException $e) {
    error_log("Orders count error: " . $e->getMessage());
    $total_orders = 0;
    $total_pages = 0;
}

// Build query for fetching orders with joins
$sql = "SELECT o.*, 
        p.name as plan_name,
        p.mrp as plan_mrp,
        p.discounted_price as plan_discounted_price,
        p.currency as plan_currency,
        u.name as customer_name,
        u.email as customer_email
        FROM orders o
        LEFT JOIN plans p ON o.plan_id = p.id
        LEFT JOIN users u ON o.customer_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND o.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($plan_filter)) {
    $sql .= " AND o.plan_id = :plan_id";
    $params[':plan_id'] = $plan_filter;
}

if (!empty($customer_filter)) {
    $sql .= " AND o.customer_id = :customer_id";
    $params[':customer_id'] = $customer_filter;
}

if (!empty($date_from)) {
    $sql .= " AND DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

if (!empty($search_query)) {
    $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR o.id LIKE :search OR o.pg_order_id LIKE :search OR o.pg_payment_id LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";

try {
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Orders list error: " . $e->getMessage());
    $orders = [];
}

// Include admin header
include_admin_header('Orders');

// Include export button helper
require_once __DIR__ . '/../includes/export_button_helper.php';
?>

<?php render_export_button_styles(); ?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Order Management</h1>
        <p class="admin-page-description">View and manage all transactions</p>
    </div>
    <div class="admin-page-header-actions">
        <?php render_export_button(get_app_base_url() . '/admin/api/export-orders.php'); ?>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/orders.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by customer, order ID, or payment ID..."
                value="<?php echo htmlspecialchars($search_query); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="status" class="admin-filter-label">Payment Status</label>
            <select id="status" name="status" class="admin-filter-select">
                <option value="">All Statuses</option>
                <option value="PENDING" <?php echo $status_filter === 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                <option value="SUCCESS" <?php echo $status_filter === 'SUCCESS' ? 'selected' : ''; ?>>Success</option>
                <option value="FAILED" <?php echo $status_filter === 'FAILED' ? 'selected' : ''; ?>>Failed</option>
                <option value="CANCELLED" <?php echo $status_filter === 'CANCELLED' ? 'selected' : ''; ?>>Cancelled</option>
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
            <a href="<?php echo get_app_base_url(); ?>/admin/orders.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Orders Table -->
<div class="admin-card">
    <?php if (empty($orders)): ?>
        <?php 
        render_empty_state(
            'No orders found',
            'No transactions match your current filters',
            '',
            ''
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>PG Details</th>
                        <th>Payment Status</th>
                        <th>Payment Method</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <code class="code-inline"><?php echo htmlspecialchars(substr($order['id'], 0, 8)); ?></code>
                            </td>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                </div>
                                <div class="table-cell-secondary">
                                    <?php echo date('g:i A', strtotime($order['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($order['customer_name']): ?>
                                    <div class="table-cell-primary">
                                        <?php echo htmlspecialchars($order['customer_name']); ?>
                                    </div>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars($order['customer_email']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($order['plan_name']): ?>
                                    <?php echo htmlspecialchars($order['plan_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">No plan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="amount-display">
                                    <span class="currency"><?php echo htmlspecialchars($order['currency']); ?></span>
                                    <span class="amount"><?php echo number_format($order['amount'], 2); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ($order['pg_order_id'] || $order['pg_payment_id']): ?>
                                    <div class="table-cell-primary">
                                        <?php if ($order['pg_order_id']): ?>
                                            <div class="pg-detail-label">Order:</div>
                                            <code class="code-inline"><?php echo htmlspecialchars(substr($order['pg_order_id'], 0, 20)); ?></code>
                                        <?php endif; ?>
                                    </div>
                                    <div class="table-cell-secondary" style="margin-top: 4px;">
                                        <?php if ($order['pg_payment_id']): ?>
                                            <div class="pg-detail-label">Payment:</div>
                                            <code class="code-inline"><?php echo htmlspecialchars(substr($order['pg_payment_id'], 0, 20)); ?></code>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_status_badge($order['status']); ?></td>
                            <td>
                                <?php if ($order['payment_method']): ?>
                                    <span class="payment-method">
                                        <?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/orders/view.php?id=<?php echo urlencode($order['id']); ?>" 
                                       class="btn btn-sm btn-primary"
                                       title="View order details">
                                        View
                                    </a>
                                    <?php if ($order['status'] === 'SUCCESS'): ?>
                                    <a href="<?php echo get_app_base_url(); ?>/admin/invoices/view.php?order_id=<?php echo urlencode($order['id']); ?>" 
                                       class="btn btn-sm btn-secondary"
                                       title="View invoice">
                                        Invoice
                                    </a>
                                    <?php endif; ?>
                                </div>
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
                $base_url = get_app_base_url() . '/admin/orders.php';
                $query_params = [];
                if (!empty($status_filter)) {
                    $query_params[] = 'status=' . urlencode($status_filter);
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
                Showing <?php echo count($orders); ?> of <?php echo $total_orders; ?> order<?php echo $total_orders !== 1 ? 's' : ''; ?>
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
    -webkit-overflow-scrolling: touch;
}

.admin-table {
    width: 100%;
    min-width: 915px; /* Sum of all column widths to prevent compression */
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
.admin-table td:nth-child(1) { width: 80px; } /* Order ID */

.admin-table th:nth-child(2),
.admin-table td:nth-child(2) { width: 95px; white-space: normal; } /* Date */

.admin-table th:nth-child(3),
.admin-table td:nth-child(3) { width: 150px; } /* Customer */

.admin-table th:nth-child(4),
.admin-table td:nth-child(4) { width: 90px; } /* Plan */

.admin-table th:nth-child(5),
.admin-table td:nth-child(5) { width: 85px; } /* Amount */

.admin-table th:nth-child(6),
.admin-table td:nth-child(6) { width: 120px; } /* Payment ID */

.admin-table th:nth-child(7),
.admin-table td:nth-child(7) { width: 90px; } /* Status */

.admin-table th:nth-child(8),
.admin-table td:nth-child(8) { width: 75px; } /* Method */

.admin-table th:nth-child(9),
.admin-table td:nth-child(9) { 
    width: 130px; 
    min-width: 130px;
    white-space: nowrap;
} /* Actions - Increased for two buttons */

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
    font-size: 11px;
    color: var(--color-gray-800);
    display: inline-block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pg-detail-label {
    font-size: 10px;
    color: var(--color-gray-500);
    font-weight: var(--font-weight-semibold);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.text-muted {
    color: var(--color-gray-500);
    font-style: italic;
}

.amount-display {
    display: flex;
    align-items: baseline;
    gap: 2px;
    white-space: nowrap;
}

.currency {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    font-weight: var(--font-weight-semibold);
}

.amount {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
}

.payment-method {
    display: inline-block;
    padding: 2px 8px;
    background-color: var(--color-gray-100);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
    text-transform: capitalize;
}

.table-actions {
    display: flex;
    gap: var(--spacing-1);
    flex-wrap: nowrap;
}

.table-actions .btn-sm {
    font-size: 11px;
    padding: 4px 8px;
    white-space: nowrap;
}

.admin-card-footer-text {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
}

@media (max-width: 1200px) {
    .admin-table {
        table-layout: auto;
        min-width: 1000px; /* Ensure minimum width to prevent button overflow */
    }
    
    .admin-table th,
    .admin-table td {
        white-space: normal;
    }
    
    /* Adjust column widths for medium screens */
    .admin-table th:nth-child(9),
    .admin-table td:nth-child(9) { 
        width: 120px; 
        min-width: 120px;
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
