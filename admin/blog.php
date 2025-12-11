<?php
/**
 * Admin Blog Posts List Page
 * Displays table of all blog posts with filters and search
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

use Karyalay\Services\ContentService;

// Start secure session
startSecureSession();

// Require admin authentication and blog.manage permission
require_admin();
require_permission('blog.manage');

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

// Fetch blog posts with filters
try {
    // Build query for counting total
    $count_sql = "SELECT COUNT(*) FROM blog_posts WHERE 1=1";
    $count_params = [];
    
    if (!empty($status_filter)) {
        $count_sql .= " AND status = :status";
        $count_params[':status'] = $status_filter;
    }
    
    if (!empty($search_query)) {
        $count_sql .= " AND (title LIKE :search OR content LIKE :search OR excerpt LIKE :search)";
        $count_params[':search'] = '%' . $search_query . '%';
    }
    
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_posts = $count_stmt->fetchColumn();
    $total_pages = ceil($total_posts / $per_page);
    
    // Build query for fetching blog posts
    $sql = "SELECT bp.*, u.name as author_name FROM blog_posts bp 
            LEFT JOIN users u ON bp.author_id = u.id 
            WHERE 1=1";
    $params = [];
    
    if (!empty($status_filter)) {
        $sql .= " AND bp.status = :status";
        $params[':status'] = $status_filter;
    }
    
    if (!empty($search_query)) {
        $sql .= " AND (bp.title LIKE :search OR bp.content LIKE :search OR bp.excerpt LIKE :search)";
        $params[':search'] = '%' . $search_query . '%';
    }
    
    $sql .= " ORDER BY bp.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $blog_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decode JSON fields
    foreach ($blog_posts as &$post) {
        if (isset($post['tags']) && is_string($post['tags'])) {
            $post['tags'] = json_decode($post['tags'], true);
        }
    }
    
} catch (PDOException $e) {
    error_log("Blog posts list error: " . $e->getMessage());
    $blog_posts = [];
    $total_posts = 0;
    $total_pages = 0;
}

// Include admin header
include_admin_header('Blog Posts');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Blog Posts</h1>
        <p class="admin-page-description">Manage blog posts displayed on the public website</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/blog/new.php" class="btn btn-primary">
            Create New Post
        </a>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/blog.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by title, content, or excerpt..."
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
            <a href="<?php echo get_app_base_url(); ?>/admin/blog.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Blog Posts Table -->
<div class="admin-card">
    <?php if (empty($blog_posts)): ?>
        <?php 
        render_empty_state(
            'No blog posts found',
            'Get started by creating your first blog post',
            get_app_base_url() . '/admin/blog/new.php',
            'Create Post'
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Tags</th>
                        <th>Published</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blog_posts as $post): ?>
                        <tr>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </div>
                                <?php if (!empty($post['excerpt'])): ?>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars(substr($post['excerpt'], 0, 60)); ?>
                                        <?php echo strlen($post['excerpt']) > 60 ? '...' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo get_status_badge($post['status']); ?></td>
                            <td>
                                <?php 
                                if (is_array($post['tags']) && !empty($post['tags'])) {
                                    echo '<div class="tag-list">';
                                    foreach (array_slice($post['tags'], 0, 2) as $tag) {
                                        echo '<span class="tag">' . htmlspecialchars($tag) . '</span>';
                                    }
                                    if (count($post['tags']) > 2) {
                                        echo '<span class="tag">+' . (count($post['tags']) - 2) . '</span>';
                                    }
                                    echo '</div>';
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($post['published_at']) {
                                    echo get_relative_time($post['published_at']);
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td><?php echo get_relative_time($post['created_at']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/blog/edit.php?id=<?php echo urlencode($post['id']); ?>" 
                                       class="btn btn-sm btn-secondary"
                                       title="Edit post">
                                        Edit
                                    </a>
                                    <a href="<?php echo get_app_base_url(); ?>/blog/<?php echo urlencode($post['slug']); ?>" 
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
                $base_url = get_app_base_url() . '/admin/blog.php';
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
                Showing <?php echo count($blog_posts); ?> of <?php echo $total_posts; ?> post<?php echo $total_posts !== 1 ? 's' : ''; ?>
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

.tag-list {
    display: flex;
    gap: var(--spacing-1);
    flex-wrap: wrap;
}

.tag {
    display: inline-block;
    padding: 2px 8px;
    background-color: var(--color-gray-100);
    color: var(--color-gray-700);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-medium);
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
