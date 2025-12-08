<?php
/**
 * Admin Ports List Page
 * Displays table of all ports with filters
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Services\PortService;
use Karyalay\Models\Plan;
use Karyalay\Models\User;

// Start secure session
startSecureSession();

// Require admin authentication
require_admin();

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize services and models
$portService = new PortService();
$planModel = new Plan();
$userModel = new User();

// Get filters from query parameters
$status_filter = $_GET['status'] ?? '';
$plan_filter = $_GET['plan'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Fetch all plans for filter dropdown
$allPlans = $planModel->findAll();

// Build query for counting total
$count_sql = "SELECT COUNT(*) FROM ports WHERE 1=1";
$count_params = [];

if (!empty($status_filter) && in_array($status_filter, ['AVAILABLE', 'RESERVED', 'ASSIGNED', 'DISABLED'])) {
    $count_sql .= " AND status = :status";
    $count_params[':status'] = $status_filter;
}

if (!empty($plan_filter)) {
    $count_sql .= " AND plan_id = :plan_id";
    $count_params[':plan_id'] = $plan_filter;
}

if (!empty($search_query)) {
    $count_sql .= " AND (instance_url LIKE :search OR db_host LIKE :search OR db_name LIKE :search OR notes LIKE :search)";
    $count_params[':search'] = '%' . $search_query . '%';
}

try {
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_ports = $count_stmt->fetchColumn();
    $total_pages = ceil($total_ports / $per_page);
} catch (PDOException $e) {
    error_log("Ports count error: " . $e->getMessage());
    $total_ports = 0;
    $total_pages = 0;
}

// Build query for fetching ports with joins
$sql = "SELECT p.*, 
        pl.name as plan_name, 
        u.name as customer_name, 
        u.email as customer_email
        FROM ports p
        LEFT JOIN plans pl ON p.plan_id = pl.id
        LEFT JOIN users u ON p.assigned_customer_id = u.id
        WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND p.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($plan_filter)) {
    $sql .= " AND p.plan_id = :plan_id";
    $params[':plan_id'] = $plan_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (p.instance_url LIKE :search OR p.db_host LIKE :search OR p.db_name LIKE :search OR p.notes LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

try {
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $ports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Ports list error: " . $e->getMessage());
    $ports = [];
}

// Include admin header
include_admin_header('Ports');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Port Management</h1>
        <p class="admin-page-description">Manage port pool and allocations</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_base_url(); ?>/admin/ports/import.php" class="btn btn-secondary">
            <span class="btn-icon">📤</span>
            Bulk Import
        </a>
        <a href="<?php echo get_base_url(); ?>/admin/ports/new.php" class="btn btn-primary">
            <span class="btn-icon">➕</span>
            Add New Port
        </a>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="/admin/ports.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by URL, database host, or notes..."
                value="<?php echo htmlspecialchars($search_query); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="status" class="admin-filter-label">Status</label>
            <select id="status" name="status" class="admin-filter-select">
                <option value="">All Statuses</option>
                <option value="AVAILABLE" <?php echo $status_filter === 'AVAILABLE' ? 'selected' : ''; ?>>Available</option>
                <option value="RESERVED" <?php echo $status_filter === 'RESERVED' ? 'selected' : ''; ?>>Reserved</option>
                <option value="ASSIGNED" <?php echo $status_filter === 'ASSIGNED' ? 'selected' : ''; ?>>Assigned</option>
                <option value="DISABLED" <?php echo $status_filter === 'DISABLED' ? 'selected' : ''; ?>>Disabled</option>
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
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_base_url(); ?>/admin/ports.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Ports Table -->
<div class="admin-card">
    <?php if (empty($ports)): ?>
        <?php 
        render_empty_state(
            'No ports found',
            'Get started by adding ports to your pool',
            '/admin/ports/new.php',
            'Add Port'
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Instance URL</th>
                        <th>Database</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Assigned Customer</th>
                        <th>Assignment Date</th>
                        <th>Region</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ports as $port): ?>
                        <tr>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($port['instance_url']); ?>
                                </div>
                                <?php if (!empty($port['notes'])): ?>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars(substr($port['notes'], 0, 40)); ?>
                                        <?php echo strlen($port['notes']) > 40 ? '...' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($port['db_host']): ?>
                                    <div class="table-cell-primary">
                                        <code class="code-inline"><?php echo htmlspecialchars($port['db_host']); ?></code>
                                    </div>
                                    <?php if ($port['db_name']): ?>
                                        <div class="table-cell-secondary">
                                            <?php echo htmlspecialchars($port['db_name']); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not configured</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($port['plan_name']): ?>
                                    <?php echo htmlspecialchars($port['plan_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">No plan</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_status_badge($port['status']); ?></td>
                            <td>
                                <?php if ($port['customer_name']): ?>
                                    <div class="table-cell-primary">
                                        <?php echo htmlspecialchars($port['customer_name']); ?>
                                    </div>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars($port['customer_email']); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($port['assigned_at']): ?>
                                    <?php echo get_relative_time($port['assigned_at']); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($port['server_region']): ?>
                                    <?php echo htmlspecialchars($port['server_region']); ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_base_url(); ?>/admin/ports/edit.php?id=<?php echo urlencode($port['id']); ?>" 
                                       class="btn btn-sm btn-secondary"
                                       title="Edit port">
                                        Edit
                                    </a>
                                    <?php if ($port['status'] !== 'ASSIGNED'): ?>
                                        <a href="<?php echo get_base_url(); ?>/admin/ports/delete.php?id=<?php echo urlencode($port['id']); ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this port?');"
                                           title="Delete port">
                                            Delete
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
                $base_url = '/admin/ports.php';
                $query_params = [];
                if (!empty($status_filter)) {
                    $query_params[] = 'status=' . urlencode($status_filter);
                }
                if (!empty($plan_filter)) {
                    $query_params[] = 'plan=' . urlencode($plan_filter);
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
                Showing <?php echo count($ports); ?> of <?php echo $total_ports; ?> port<?php echo $total_ports !== 1 ? 's' : ''; ?>
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

.table-actions {
    display: flex;
    gap: var(--spacing-2);
}

.btn-icon {
    margin-right: var(--spacing-1);
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
