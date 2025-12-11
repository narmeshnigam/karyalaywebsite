<?php
/**
 * Admin Solutions List Page
 * Displays table of all solutions with filters and search
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Services\ContentService;

startSecureSession();
require_admin();
require_permission('solutions.manage');

$db = \Karyalay\Database\Connection::getInstance();
$contentService = new ContentService();

$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$filters = [];
if (!empty($status_filter) && in_array($status_filter, ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
    $filters['status'] = $status_filter;
}

try {
    $count_sql = "SELECT COUNT(*) FROM solutions WHERE 1=1";
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
    $total_solutions = $count_stmt->fetchColumn();
    $total_pages = ceil($total_solutions / $per_page);
    
    $sql = "SELECT * FROM solutions WHERE 1=1";
    $params = [];
    
    if (!empty($status_filter)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status_filter;
    }
    
    if (!empty($search_query)) {
        $sql .= " AND (name LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $search_query . '%';
    }
    
    $sql .= " ORDER BY display_order ASC, created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($solutions as &$solution) {
        if (isset($solution['features']) && is_string($solution['features'])) {
            $solution['features'] = json_decode($solution['features'], true);
        }
        if (isset($solution['screenshots']) && is_string($solution['screenshots'])) {
            $solution['screenshots'] = json_decode($solution['screenshots'], true);
        }
        if (isset($solution['faqs']) && is_string($solution['faqs'])) {
            $solution['faqs'] = json_decode($solution['faqs'], true);
        }
    }
    
} catch (PDOException $e) {
    error_log("Solutions list error: " . $e->getMessage());
    $solutions = [];
    $total_solutions = 0;
    $total_pages = 0;
}

include_admin_header('Solutions');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Solutions</h1>
        <p class="admin-page-description">Manage product solutions displayed on the public website</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/solutions/new.php" class="btn btn-primary">
            Create New Solution
        </a>
    </div>
</div>

<?php if (isset($_SESSION['admin_success'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['admin_success']); ?>
        <?php unset($_SESSION['admin_success']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($_SESSION['admin_error']); ?>
        <?php unset($_SESSION['admin_error']); ?>
    </div>
<?php endif; ?>

<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/solutions.php" class="admin-filters-form">
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
                <option value="DRAFT" <?php echo $status_filter === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                <option value="PUBLISHED" <?php echo $status_filter === 'PUBLISHED' ? 'selected' : ''; ?>>Published</option>
                <option value="ARCHIVED" <?php echo $status_filter === 'ARCHIVED' ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>
        
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/solutions.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<div class="admin-card">
    <?php if (empty($solutions)): ?>
        <?php 
        render_empty_state(
            'No solutions found',
            'Get started by creating your first solution',
            get_app_base_url() . '/admin/solutions/new.php',
            'Create Solution'
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Features</th>
                        <th>Display Order</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solutions as $solution): ?>
                        <tr>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($solution['name']); ?>
                                </div>
                                <?php if (!empty($solution['description'])): ?>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars(substr($solution['description'], 0, 60)); ?>
                                        <?php echo strlen($solution['description']) > 60 ? '...' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code class="code-inline"><?php echo htmlspecialchars($solution['slug']); ?></code>
                            </td>
                            <td><?php echo get_status_badge($solution['status']); ?></td>
                            <td>
                                <?php 
                                $feature_count = is_array($solution['features']) ? count($solution['features']) : 0;
                                echo $feature_count . ' feature' . ($feature_count !== 1 ? 's' : '');
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($solution['display_order']); ?></td>
                            <td><?php echo get_relative_time($solution['created_at']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/solutions/edit.php?id=<?php echo urlencode($solution['id']); ?>" 
                                       class="btn btn-sm btn-secondary"
                                       title="Edit solution">
                                        Edit
                                    </a>
                                    <a href="<?php echo get_app_base_url(); ?>/solution/<?php echo urlencode($solution['slug']); ?>" 
                                       class="btn btn-sm btn-text"
                                       target="_blank"
                                       title="View on site">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="admin-card-footer">
                <?php 
                $base_url = get_app_base_url() . '/admin/solutions.php';
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
                Showing <?php echo count($solutions); ?> of <?php echo $total_solutions; ?> solution<?php echo $total_solutions !== 1 ? 's' : ''; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    gap: 16px;
}
.admin-page-header-content { flex: 1; }
.admin-page-title {
    font-size: 24px;
    font-weight: 700;
    color: var(--color-gray-900);
    margin: 0 0 8px 0;
}
.admin-page-description {
    font-size: 14px;
    color: var(--color-gray-600);
    margin: 0;
}
.admin-page-header-actions {
    display: flex;
    gap: 12px;
}
.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}
.alert-success {
    background-color: #d1fae5;
    border: 1px solid #6ee7b7;
    color: #065f46;
}
.alert-error {
    background-color: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}
.admin-filters-section {
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
}
.admin-filters-form {
    display: flex;
    gap: 16px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.admin-filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    flex: 1;
    min-width: 200px;
}
.admin-filter-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-gray-700);
}
.admin-filter-input,
.admin-filter-select {
    padding: 8px 12px;
    border: 1px solid var(--color-gray-300);
    border-radius: 6px;
    font-size: 14px;
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
    gap: 8px;
}
.table-cell-primary {
    font-weight: 600;
    color: var(--color-gray-900);
}
.table-cell-secondary {
    font-size: 13px;
    color: var(--color-gray-600);
    margin-top: 4px;
}
.code-inline {
    background-color: var(--color-gray-100);
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: var(--color-gray-800);
}
.table-actions {
    display: flex;
    gap: 8px;
}
.btn-icon {
    margin-right: 4px;
}
.admin-card-footer-text {
    font-size: 14px;
    color: var(--color-gray-600);
    margin: 0;
}
@media (max-width: 768px) {
    .admin-page-header { flex-direction: column; }
    .admin-filters-form { flex-direction: column; }
    .admin-filter-group { width: 100%; }
}
</style>

<?php include_admin_footer(); ?>
