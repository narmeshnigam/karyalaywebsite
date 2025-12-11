<?php
/**
 * Admin Customers List Page
 * Displays all user accounts with filters
 * Requirements: 10.1
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

use Karyalay\Models\User;

// Start secure session
startSecureSession();

// Require admin authentication and customers.view permission
require_admin();
require_permission('customers.view');

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize models
$userModel = new User();

// Get filters from query parameters
$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query for counting total customers
// Include users with CUSTOMER role or no admin roles (ADMIN, SUPPORT, SALES, CONTENT_EDITOR)
$count_sql = "SELECT COUNT(*) FROM users WHERE (UPPER(role) = 'CUSTOMER' OR role IS NULL OR UPPER(role) NOT IN ('ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR'))";
$count_params = [];

if (!empty($status_filter)) {
    if ($status_filter === 'active') {
        $count_sql .= " AND id IN (SELECT DISTINCT customer_id FROM subscriptions WHERE status = 'ACTIVE')";
    } elseif ($status_filter === 'inactive') {
        $count_sql .= " AND id NOT IN (SELECT DISTINCT customer_id FROM subscriptions WHERE status = 'ACTIVE')";
    } elseif ($status_filter === 'expired') {
        $count_sql .= " AND id IN (SELECT DISTINCT customer_id FROM subscriptions WHERE status = 'EXPIRED')";
    }
}

if (!empty($search_query)) {
    $count_sql .= " AND (name LIKE :search OR email LIKE :search OR business_name LIKE :search OR phone LIKE :search)";
    $count_params[':search'] = '%' . $search_query . '%';
}

try {
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_customers = $count_stmt->fetchColumn();
    $total_pages = ceil($total_customers / $per_page);
} catch (PDOException $e) {
    error_log("Customers count error: " . $e->getMessage());
    $total_customers = 0;
    $total_pages = 0;
}

// Build query for fetching customers with subscription status
// Include users with CUSTOMER role or no admin roles (ADMIN, SUPPORT, SALES, CONTENT_EDITOR)
$sql = "SELECT u.*, 
        COUNT(DISTINCT s.id) as total_subscriptions,
        COUNT(DISTINCT CASE WHEN s.status = 'ACTIVE' THEN s.id END) as active_subscriptions,
        MAX(s.end_date) as latest_subscription_end,
        (SELECT status FROM subscriptions WHERE customer_id = u.id ORDER BY end_date DESC LIMIT 1) as latest_subscription_status
        FROM users u
        LEFT JOIN subscriptions s ON u.id = s.customer_id
        WHERE (UPPER(u.role) = 'CUSTOMER' OR u.role IS NULL OR UPPER(u.role) NOT IN ('ADMIN', 'SUPPORT', 'SALES', 'CONTENT_EDITOR'))";
$params = [];

if (!empty($status_filter)) {
    if ($status_filter === 'active') {
        $sql .= " AND u.id IN (SELECT DISTINCT customer_id FROM subscriptions WHERE status = 'ACTIVE')";
    } elseif ($status_filter === 'inactive') {
        $sql .= " AND u.id NOT IN (SELECT DISTINCT customer_id FROM subscriptions WHERE status = 'ACTIVE')";
    } elseif ($status_filter === 'expired') {
        $sql .= " AND u.id IN (SELECT DISTINCT customer_id FROM subscriptions WHERE status = 'EXPIRED')";
    }
}

if (!empty($search_query)) {
    $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.business_name LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";

try {
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Customers list error: " . $e->getMessage());
    $customers = [];
}

// Include admin header
include_admin_header('Customers');

// Include export button helper
require_once __DIR__ . '/../includes/export_button_helper.php';
?>

<?php render_export_button_styles(); ?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Customer Management</h1>
        <p class="admin-page-description">View and manage customer accounts</p>
    </div>
    <div class="admin-page-header-actions">
        <?php render_export_button(get_app_base_url() . '/admin/api/export-customers.php'); ?>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/customers.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by name, email, business, or phone..."
                value="<?php echo htmlspecialchars($search_query); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="status" class="admin-filter-label">Subscription Status</label>
            <select id="status" name="status" class="admin-filter-select">
                <option value="">All Customers</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Subscription</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>No Active Subscription</option>
                <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired Subscription</option>
            </select>
        </div>
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/customers.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Customers Table -->
<div class="admin-card">
    <?php if (empty($customers)): ?>
        <?php 
        render_empty_state(
            'No customers found',
            'No customer accounts match your current filters',
            '',
            ''
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Business Name</th>
                        <th>Contact Info</th>
                        <th>Registration Date</th>
                        <th>Subscription Status</th>
                        <th>Total Subscriptions</th>
                        <th>Latest Expiry</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </div>
                                <div class="table-cell-secondary">
                                    <?php echo htmlspecialchars($customer['email']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($customer['business_name'])): ?>
                                    <?php echo htmlspecialchars($customer['business_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($customer['phone'])): ?>
                                    <?php echo htmlspecialchars($customer['phone']); ?>
                                <?php else: ?>
                                    <span class="text-muted">No phone</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_relative_time($customer['created_at']); ?></td>
                            <td>
                                <?php if ($customer['active_subscriptions'] > 0): ?>
                                    <?php echo get_status_badge('ACTIVE'); ?>
                                <?php elseif ($customer['total_subscriptions'] > 0): ?>
                                    <?php 
                                    if ($customer['latest_subscription_status']) {
                                        echo get_status_badge($customer['latest_subscription_status']);
                                    } else {
                                        echo get_status_badge('EXPIRED');
                                    }
                                    ?>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No Subscription</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="subscription-count">
                                    <span class="count-active"><?php echo $customer['active_subscriptions']; ?> active</span>
                                    <span class="count-total">/ <?php echo $customer['total_subscriptions']; ?> total</span>
                                </div>
                            </td>
                            <td>
                                <?php if ($customer['latest_subscription_end']): ?>
                                    <?php 
                                    $end_date = strtotime($customer['latest_subscription_end']);
                                    $now = time();
                                    if ($end_date < $now) {
                                        echo '<span class="text-danger">' . date('M j, Y', $end_date) . '</span>';
                                    } else {
                                        echo date('M j, Y', $end_date);
                                    }
                                    ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/customers/view.php?id=<?php echo urlencode($customer['id']); ?>" 
                                       class="btn btn-sm btn-primary"
                                       title="View customer details">
                                        View
                                    </a>
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
                $base_url = get_app_base_url() . '/admin/customers.php';
                $query_params = [];
                if (!empty($status_filter)) {
                    $query_params[] = 'status=' . urlencode($status_filter);
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
                Showing <?php echo count($customers); ?> of <?php echo $total_customers; ?> customer<?php echo $total_customers !== 1 ? 's' : ''; ?>
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
    min-width: 200px;
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
.admin-table td:nth-child(1) { width: 180px; } /* Customer */

.admin-table th:nth-child(2),
.admin-table td:nth-child(2) { width: 140px; } /* Business Name */

.admin-table th:nth-child(3),
.admin-table td:nth-child(3) { width: 120px; } /* Contact Info */

.admin-table th:nth-child(4),
.admin-table td:nth-child(4) { width: 110px; } /* Registration Date */

.admin-table th:nth-child(5),
.admin-table td:nth-child(5) { width: 120px; } /* Subscription Status */

.admin-table th:nth-child(6),
.admin-table td:nth-child(6) { width: 130px; white-space: normal; } /* Total Subscriptions */

.admin-table th:nth-child(7),
.admin-table td:nth-child(7) { width: 100px; } /* Latest Expiry */

.admin-table th:nth-child(8),
.admin-table td:nth-child(8) { width: 90px; } /* Actions */

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

.text-muted {
    color: var(--color-gray-500);
    font-style: italic;
}

.text-danger {
    color: var(--color-red-600);
    font-weight: var(--font-weight-semibold);
}

.subscription-count {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-1);
}

.count-active {
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
}

.count-total {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
}

.table-actions {
    display: flex;
    gap: var(--spacing-2);
}

.admin-card-footer-text {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
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
