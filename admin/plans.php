<?php
/**
 * Admin Plans List Page
 * Displays table of all plans with filters and search
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Services\PlanService;

// Start secure session
startSecureSession();

// Require admin authentication
require_admin();

// Generate CSRF token for delete forms
$csrf_token = getCsrfToken();

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize PlanService
$planService = new PlanService();

// Get filters from query parameters
$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build filters array
$filters = [];
if (!empty($status_filter) && in_array($status_filter, ['ACTIVE', 'INACTIVE'])) {
    $filters['status'] = $status_filter;
}

// Fetch plans with filters
try {
    // Build query for counting total
    $count_sql = "SELECT COUNT(*) FROM plans WHERE 1=1";
    $count_params = [];
    
    if (!empty($status_filter)) {
        $count_sql .= " AND status = :status";
        $count_params[':status'] = $status_filter;
    }
    
    if (!empty($search_query)) {
        $count_sql .= " AND (name LIKE :search OR description LIKE :search)";
        $count_params[':search'] = '%' . $search_query . '%';
    }
    
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_plans = $count_stmt->fetchColumn();
    $total_pages = ceil($total_plans / $per_page);
    
    // Build query for fetching plans
    $sql = "SELECT * FROM plans WHERE 1=1";
    $params = [];
    
    if (!empty($status_filter)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status_filter;
    }
    
    if (!empty($search_query)) {
        $sql .= " AND (name LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $search_query . '%';
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decode JSON fields
    foreach ($plans as &$plan) {
        if (isset($plan['features']) && is_string($plan['features'])) {
            $plan['features'] = json_decode($plan['features'], true);
        }
    }
    
} catch (PDOException $e) {
    error_log("Plans list error: " . $e->getMessage());
    $plans = [];
    $total_plans = 0;
    $total_pages = 0;
}

// Include admin header
include_admin_header('Plans');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Plans</h1>
        <p class="admin-page-description">Manage subscription plans and pricing</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_base_url(); ?>/admin/plans/new.php" class="btn btn-primary">
            <span class="btn-icon">➕</span>
            Create New Plan
        </a>
    </div>
</div>

<!-- Flash Messages -->
<?php if (isset($_SESSION['admin_success'])): ?>
    <div class="alert alert-success" role="alert">
        <?php echo htmlspecialchars($_SESSION['admin_success']); ?>
        <?php unset($_SESSION['admin_success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-error" role="alert">
        <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
        <?php unset($_SESSION['admin_error']); ?>
    </div>
<?php endif; ?>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_base_url(); ?>/admin/plans.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by name or description..."
                value="<?php echo htmlspecialchars($search_query); ?>"
            >
        </div>
        
        <div class="admin-filter-group">
            <label for="status" class="admin-filter-label">Status</label>
            <select id="status" name="status" class="admin-filter-select">
                <option value="">All Statuses</option>
                <option value="ACTIVE" <?php echo $status_filter === 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                <option value="INACTIVE" <?php echo $status_filter === 'INACTIVE' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_base_url(); ?>/admin/plans.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Plans Table -->
<div class="admin-card">
    <?php if (empty($plans)): ?>
        <?php 
        render_empty_state(
            'No plans found',
            'Get started by creating your first subscription plan',
            get_base_url() . '/admin/plans/new.php',
            'Create Plan'
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Price</th>
                        <th>Billing Period</th>
                        <th>Status</th>
                        <th>Features</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($plan['name']); ?>
                                </div>
                                <?php if (!empty($plan['description'])): ?>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars(substr($plan['description'], 0, 60)); ?>
                                        <?php echo strlen($plan['description']) > 60 ? '...' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code class="code-inline"><?php echo htmlspecialchars($plan['slug']); ?></code>
                            </td>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($plan['currency'] ?? 'USD'); ?> 
                                    <?php echo number_format($plan['price'], 2); ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $months = $plan['billing_period_months'];
                                if ($months == 1) {
                                    echo 'Monthly';
                                } elseif ($months == 12) {
                                    echo 'Yearly';
                                } else {
                                    echo $months . ' months';
                                }
                                ?>
                            </td>
                            <td><?php echo get_status_badge($plan['status']); ?></td>
                            <td>
                                <?php 
                                $feature_count = is_array($plan['features']) ? count($plan['features']) : 0;
                                echo $feature_count . ' feature' . ($feature_count !== 1 ? 's' : '');
                                ?>
                            </td>
                            <td><?php echo get_relative_time($plan['created_at']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_base_url(); ?>/admin/plans/view.php?id=<?php echo urlencode($plan['id']); ?>" 
                                       class="btn btn-sm btn-outline"
                                       title="View plan details">
                                        View
                                    </a>
                                    <a href="<?php echo get_base_url(); ?>/admin/plans/edit.php?id=<?php echo urlencode($plan['id']); ?>" 
                                       class="btn btn-sm btn-secondary"
                                       title="Edit plan">
                                        Edit
                                    </a>
                                    <form method="POST" 
                                          action="<?php echo get_base_url(); ?>/admin/plans/delete.php?id=<?php echo urlencode($plan['id']); ?>" 
                                          class="delete-form"
                                          onsubmit="return confirm('Are you sure you want to delete this plan? This action cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete plan">
                                            Delete
                                        </button>
                                    </form>
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
                $base_url = get_base_url() . '/admin/plans.php';
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
                render_admin_pagination($page, $total_pages, $base_url);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-card-footer">
            <p class="admin-card-footer-text">
                Showing <?php echo count($plans); ?> of <?php echo $total_plans; ?> plan<?php echo $total_plans !== 1 ? 's' : ''; ?>
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

.table-actions {
    display: flex;
    gap: var(--spacing-2);
    align-items: center;
}

.table-actions .delete-form {
    display: inline;
    margin: 0;
}

.btn-outline {
    background-color: transparent;
    border: 1px solid var(--color-gray-300);
    color: var(--color-gray-700);
}

.btn-outline:hover {
    background-color: var(--color-gray-50);
    border-color: var(--color-gray-400);
}

.alert {
    padding: var(--spacing-4);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-4);
    font-size: var(--font-size-base);
}

.alert-success {
    background-color: #d1fae5;
    border: 1px solid #10b981;
    color: #065f46;
}

.alert-error {
    background-color: #fee2e2;
    border: 1px solid #ef4444;
    color: #991b1b;
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
}
</style>

<?php include_admin_footer(); ?>
