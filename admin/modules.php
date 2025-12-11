<?php
/**
 * Admin Modules List Page
 * Displays table of all modules with filters and search
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

use Karyalay\Services\ContentService;

// Start secure session
startSecureSession();

// Require admin authentication and solutions.manage permission
require_admin();
require_permission('solutions.manage');

// Get database connection
$db = \Karyalay\Database\Connection::getInstance();

// Initialize ContentService
$contentService = new ContentService();

// Get filters from query parameters
$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build filters array
$filters = [];
if (!empty($status_filter) && in_array($status_filter, ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
    $filters['status'] = $status_filter;
}

// Fetch modules with filters
try {
    // Build query for counting total
    $count_sql = "SELECT COUNT(*) FROM modules WHERE 1=1";
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
    $total_modules = $count_stmt->fetchColumn();
    $total_pages = ceil($total_modules / $per_page);
    
    // Build query for fetching modules
    $sql = "SELECT * FROM modules WHERE 1=1";
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
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decode JSON fields
    foreach ($modules as &$module) {
        if (isset($module['features']) && is_string($module['features'])) {
            $module['features'] = json_decode($module['features'], true);
        }
        if (isset($module['screenshots']) && is_string($module['screenshots'])) {
            $module['screenshots'] = json_decode($module['screenshots'], true);
        }
        if (isset($module['faqs']) && is_string($module['faqs'])) {
            $module['faqs'] = json_decode($module['faqs'], true);
        }
    }
    
} catch (PDOException $e) {
    error_log("Modules list error: " . $e->getMessage());
    $modules = [];
    $total_modules = 0;
    $total_pages = 0;
}

// Include admin header
include_admin_header('Modules');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Modules</h1>
        <p class="admin-page-description">Manage product modules displayed on the public website</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/modules/new.php" class="btn btn-primary">
            Create New Module
        </a>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/modules.php" class="admin-filters-form">
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
            <a href="<?php echo get_app_base_url(); ?>/admin/modules.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Modules Table -->
<div class="admin-card">
    <?php if (empty($modules)): ?>
        <?php 
        render_empty_state(
            'No modules found',
            'Get started by creating your first module',
            '/admin/modules/new.php',
            'Create Module'
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
                    <?php foreach ($modules as $module): ?>
                        <tr>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($module['name']); ?>
                                </div>
                                <?php if (!empty($module['description'])): ?>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars(substr($module['description'], 0, 60)); ?>
                                        <?php echo strlen($module['description']) > 60 ? '...' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code class="code-inline"><?php echo htmlspecialchars($module['slug']); ?></code>
                            </td>
                            <td><?php echo get_status_badge($module['status']); ?></td>
                            <td>
                                <?php 
                                $feature_count = is_array($module['features']) ? count($module['features']) : 0;
                                echo $feature_count . ' feature' . ($feature_count !== 1 ? 's' : '');
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($module['display_order']); ?></td>
                            <td><?php echo get_relative_time($module['created_at']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/modules/edit.php?id=<?php echo urlencode($module['id']); ?>" 
                                       class="btn btn-sm btn-secondary"
                                       title="Edit module">
                                        Edit
                                    </a>
                                    <a href="<?php echo get_app_base_url(); ?>/module/<?php echo urlencode($module['slug']); ?>" 
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
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="admin-card-footer">
                <?php 
                $base_url = '/admin/modules.php';
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
                Showing <?php echo count($modules); ?> of <?php echo $total_modules; ?> module<?php echo $total_modules !== 1 ? 's' : ''; ?>
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
