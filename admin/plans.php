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

// Require admin authentication and plans.view permission
require_admin();
require_permission('plans.view');

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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
    
    // Build query for fetching plans with active subscription count
    $sql = "SELECT 
        p.id, p.name, p.slug, p.description, p.currency, p.billing_period_months, 
        p.status, p.created_at, p.updated_at, p.number_of_users, 
        p.allowed_storage_gb, p.mrp, p.discounted_price,
        COUNT(CASE WHEN s.status = 'ACTIVE' THEN 1 END) as active_subscriptions
        FROM plans p
        LEFT JOIN subscriptions s ON p.id = s.plan_id
        WHERE 1=1";
    $params = [];
    
    if (!empty($status_filter)) {
        $sql .= " AND p.status = :status";
        $params[':status'] = $status_filter;
    }
    
    if (!empty($search_query)) {
        $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        $params[':search'] = '%' . $search_query . '%';
    }
    
    $sql .= " GROUP BY p.id, p.name, p.slug, p.description, p.currency, p.billing_period_months, 
              p.status, p.created_at, p.updated_at, p.number_of_users, p.allowed_storage_gb, p.mrp, p.discounted_price";
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($sql);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    // Fetch all plans
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Plans list error: " . $e->getMessage());
    $plans = [];
    $total_plans = 0;
    $total_pages = 0;
}

// Include admin header
include_admin_header('Plans');

// Include export button helper
require_once __DIR__ . '/../includes/export_button_helper.php';
?>

<?php render_export_button_styles(); ?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Plans</h1>
        <p class="admin-page-description">Manage subscription plans and pricing</p>
    </div>
    <div class="admin-page-header-actions">
        <?php render_export_button(get_app_base_url() . '/admin/api/export-plans.php'); ?>
        <a href="<?php echo get_app_base_url(); ?>/admin/plans/new.php" class="btn btn-primary">
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
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/plans.php" class="admin-filters-form">
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
            <a href="<?php echo get_app_base_url(); ?>/admin/plans.php" class="btn btn-text">Clear</a>
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
            get_app_base_url() . '/admin/plans/new.php',
            'Create Plan'
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Pricing</th>
                        <th>Duration</th>
                        <th>Limits</th>
                        <th>Status</th>
                        <th>Active Users</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan): ?>
                        <?php 
                        $hasDiscount = !empty($plan['mrp']) && !empty($plan['discounted_price']) && $plan['mrp'] > $plan['discounted_price'];
                        $effectivePrice = !empty($plan['discounted_price']) ? $plan['discounted_price'] : $plan['mrp'];
                        $discountPct = $hasDiscount ? round((($plan['mrp'] - $plan['discounted_price']) / $plan['mrp']) * 100) : 0;
                        ?>
                        <tr data-plan-id="<?php echo htmlspecialchars($plan['id']); ?>" data-plan-name="<?php echo htmlspecialchars($plan['name']); ?>">
                            <td>
                                <div class="table-cell-primary"><?php echo htmlspecialchars($plan['name']); ?></div>
                                <code class="code-inline"><?php echo htmlspecialchars($plan['slug']); ?></code>
                            </td>
                            <td>
                                <?php if ($hasDiscount): ?>
                                    <div class="price-with-discount">
                                        <span class="price-mrp"><?php echo format_price($plan['mrp'], false); ?></span>
                                        <span class="price-discount-badge">-<?php echo $discountPct; ?>%</span>
                                    </div>
                                <?php endif; ?>
                                <div class="table-cell-primary">
                                    <?php echo format_price($effectivePrice, false); ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $months = $plan['billing_period_months'];
                                $durationLabel = match((int)$months) {
                                    1 => 'Monthly',
                                    3 => 'Quarterly',
                                    6 => 'Semi-Annual',
                                    12 => 'Annual',
                                    default => $months . ' months'
                                };
                                ?>
                                <span class="duration-badge duration-<?php echo $months; ?>"><?php echo $durationLabel; ?></span>
                            </td>
                            <td>
                                <div class="limits-info">
                                    <span title="Users"><?php echo !empty($plan['number_of_users']) ? $plan['number_of_users'] . ' users' : '∞ users'; ?></span>
                                    <span title="Storage"><?php echo !empty($plan['allowed_storage_gb']) ? $plan['allowed_storage_gb'] . ' GB' : '∞ storage'; ?></span>
                                </div>
                            </td>
                            <td><?php echo get_status_badge($plan['status']); ?></td>
                            <td>
                                <div class="active-users-cell">
                                    <span class="active-users-count"><?php echo (int)$plan['active_subscriptions']; ?></span>
                                    <span class="active-users-label">active</span>
                                </div>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/plans/view.php?id=<?php echo urlencode($plan['id']); ?>" class="btn btn-sm btn-outline">View</a>
                                    <a href="<?php echo get_app_base_url(); ?>/admin/plans/edit.php?id=<?php echo urlencode($plan['id']); ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/plans/delete.php?id=<?php echo urlencode($plan['id']); ?>" class="delete-form" onsubmit="return confirm('Delete this plan?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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
                $base_url = get_app_base_url() . '/admin/plans.php';
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

.price-with-discount {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    margin-bottom: 2px;
}

.price-mrp {
    font-size: var(--font-size-sm);
    color: var(--color-gray-500);
    text-decoration: line-through;
}

.price-discount-badge {
    font-size: 10px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 2px 6px;
    border-radius: var(--radius-full);
    font-weight: var(--font-weight-semibold);
}

.duration-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
}

.duration-1 { background: #dbeafe; color: #1e40af; }
.duration-3 { background: #fef3c7; color: #92400e; }
.duration-6 { background: #fce7f3; color: #9d174d; }
.duration-12 { background: #d1fae5; color: #065f46; }

.limits-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
}

.active-users-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.active-users-count {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-primary);
}

.active-users-label {
    font-size: var(--font-size-xs);
    color: var(--color-gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
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

<script>
// Debug: Log plans data to console
console.group('Plans Debug Information');
console.log('Total plans fetched:', <?php echo count($plans); ?>);
console.log('SQL Query:', <?php echo json_encode($sql ?? 'N/A'); ?>);
console.log('Plans data:');
console.log('Plans array:', <?php echo json_encode($plans); ?>);
<?php foreach ($plans as $index => $plan): ?>
console.log('Plan <?php echo $index + 1; ?> (index <?php echo $index; ?>):', {
    id: <?php echo json_encode($plan['id']); ?>,
    name: <?php echo json_encode($plan['name']); ?>,
    slug: <?php echo json_encode($plan['slug']); ?>,
    mrp: <?php echo json_encode($plan['mrp'] ?? null); ?>,
    discounted_price: <?php echo json_encode($plan['discounted_price'] ?? null); ?>,
    currency: <?php echo json_encode($plan['currency']); ?>,
    billing_period_months: <?php echo json_encode($plan['billing_period_months']); ?>,
    number_of_users: <?php echo json_encode($plan['number_of_users'] ?? null); ?>,
    allowed_storage_gb: <?php echo json_encode($plan['allowed_storage_gb'] ?? null); ?>,
    status: <?php echo json_encode($plan['status']); ?>,
    created_at: <?php echo json_encode($plan['created_at']); ?>
});
<?php endforeach; ?>
console.groupEnd();

// Debug: Check what's actually rendered in the table
console.group('🔍 Rendered Table Rows');
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr[data-plan-id]');
    console.log('Total rows rendered:', rows.length);
    rows.forEach((row, index) => {
        const cells = row.querySelectorAll('td');
        console.log(`Row ${index + 1}:`, {
            planId: row.dataset.planId,
            planName: row.dataset.planName,
            nameCell: cells[0]?.textContent.trim(),
            priceCell: cells[1]?.textContent.trim(),
            durationCell: cells[2]?.textContent.trim(),
            limitsCell: cells[3]?.textContent.trim(),
            statusCell: cells[4]?.textContent.trim()
        });
    });
    console.groupEnd();
});
</script>

<?php include_admin_footer(); ?>
