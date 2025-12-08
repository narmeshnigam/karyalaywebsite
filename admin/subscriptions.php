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

// Require admin authentication
require_admin();

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize models
$subscriptionModel = new Subscription();
$planModel = new Plan();
$userModel = new User();

// Get filters from query parameters
$status_filter = $_GET['status'] ?? '';
$plan_filter = $_GET['plan'] ?? '';
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
        p.price as plan_price,
        p.currency as plan_currency,
        u.name as customer_name,
        u.email as customer_email,
        port.instance_url as port_url,
        port.port_number as port_number
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
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Subscriptions list error: " . $e->getMessage());
    $subscriptions = [];
}

// Include admin header
include_admin_header('Subscriptions');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Subscription Management</h1>
        <p class="admin-page-description">View and manage customer subscriptions</p>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="/admin/subscriptions.php" class="admin-filters-form">
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
            <a href="/karyalayportal/admin/subscriptions.php" class="btn btn-text">Clear</a>
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
                                    <div class="table-cell-primary">
                                        <?php echo htmlspecialchars($subscription['customer_name']); ?>
                                    </div>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars($subscription['customer_email']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($subscription['plan_name']): ?>
                                    <div class="table-cell-primary">
                                        <?php echo htmlspecialchars($subscription['plan_name']); ?>
                                    </div>
                                    <?php if ($subscription['plan_price']): ?>
                                        <div class="table-cell-secondary">
                                            <?php echo htmlspecialchars($subscription['plan_currency']); ?> 
                                            <?php echo number_format($subscription['plan_price'], 2); ?>
                                        </div>
                                    <?php endif; ?>
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
                                    <div class="table-cell-primary">
                                        <?php echo htmlspecialchars($subscription['port_url']); ?>
                                    </div>
                                    <?php if ($subscription['port_number']): ?>
                                        <div class="table-cell-secondary">
                                            Port: <?php echo htmlspecialchars($subscription['port_number']); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">No port assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="/karyalayportal/admin/customers/view.php?id=<?php echo urlencode($subscription['customer_id']); ?>" 
                                       class="btn btn-sm btn-secondary"
                                       title="View customer">
                                        View Customer
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
                $base_url = '/admin/subscriptions.php';
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

.text-danger {
    color: var(--color-red-600);
    font-weight: var(--font-weight-semibold);
}

.text-warning {
    color: var(--color-orange-600);
    font-weight: var(--font-weight-semibold);
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
    
    .admin-table-container {
        overflow-x: auto;
    }
}
</style>

<?php include_admin_footer(); ?>
