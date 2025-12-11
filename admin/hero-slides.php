<?php
/**
 * Admin Hero Slides List Page
 * Displays table of all hero slides with filters and search
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

use Karyalay\Models\HeroSlide;

startSecureSession();
require_admin();
require_permission('hero_slides.manage');

$heroSlideModel = new HeroSlide();

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
if (!empty($search_query)) {
    $filters['search'] = $search_query;
}

// Fetch slides
$slides = $heroSlideModel->getAll($filters, $per_page, $offset);
$total_slides = $heroSlideModel->count($filters);
$total_pages = ceil($total_slides / $per_page);

include_admin_header('Hero Slides');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Hero Slides</h1>
        <p class="admin-page-description">Manage home page hero slider content</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/hero-slides/new.php" class="btn btn-primary">
            Add New Slide
        </a>
    </div>
</div>

<!-- Filters and Search -->
<div class="admin-filters-section">
    <form method="GET" action="<?php echo get_app_base_url(); ?>/admin/hero-slides.php" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="search" class="admin-filter-label">Search</label>
            <input 
                type="text" 
                id="search" 
                name="search" 
                class="admin-filter-input" 
                placeholder="Search by title or subtitle..."
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
            <a href="<?php echo get_app_base_url(); ?>/admin/hero-slides.php" class="btn btn-text">Clear</a>
        </div>
    </form>
</div>

<!-- Slides Table -->
<div class="admin-card">
    <?php if (empty($slides)): ?>
        <?php 
        render_empty_state(
            'No hero slides found',
            'Get started by creating your first slide',
            '/admin/hero-slides/new.php',
            'Add Slide'
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Title</th>
                        <th>Link</th>
                        <th>Status</th>
                        <th>Order</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($slides as $slide): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($slide['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($slide['title']); ?>"
                                     class="slide-preview-thumb">
                            </td>
                            <td>
                                <div class="table-cell-primary">
                                    <?php echo htmlspecialchars($slide['title']); ?>
                                </div>
                                <?php if (!empty($slide['subtitle'])): ?>
                                    <div class="table-cell-secondary">
                                        <?php echo htmlspecialchars(substr($slide['subtitle'], 0, 60)); ?>
                                        <?php echo strlen($slide['subtitle']) > 60 ? '...' : ''; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($slide['link_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($slide['link_url']); ?>" target="_blank" class="link-preview">
                                        <?php echo htmlspecialchars($slide['link_text'] ?: 'View Link'); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">No link</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo get_status_badge($slide['status']); ?></td>
                            <td><?php echo htmlspecialchars($slide['display_order']); ?></td>
                            <td><?php echo get_relative_time($slide['created_at']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/hero-slides/edit.php?id=<?php echo urlencode($slide['id']); ?>" 
                                       class="btn btn-sm btn-secondary">Edit</a>
                                    <a href="<?php echo get_app_base_url(); ?>/admin/hero-slides/delete.php?id=<?php echo urlencode($slide['id']); ?>" 
                                       class="btn btn-sm btn-text btn-danger"
                                       onclick="return confirm('Are you sure you want to delete this slide?');">Delete</a>
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
                $base_url = '/admin/hero-slides.php';
                $query_params = [];
                if (!empty($status_filter)) $query_params[] = 'status=' . urlencode($status_filter);
                if (!empty($search_query)) $query_params[] = 'search=' . urlencode($search_query);
                if (!empty($query_params)) $base_url .= '?' . implode('&', $query_params);
                render_pagination($page, $total_pages, $base_url);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-card-footer">
            <p class="admin-card-footer-text">
                Showing <?php echo count($slides); ?> of <?php echo $total_slides; ?> slide<?php echo $total_slides !== 1 ? 's' : ''; ?>
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

.slide-preview-thumb {
    width: 80px;
    height: 45px;
    object-fit: cover;
    border-radius: var(--radius-md);
    border: 1px solid var(--color-gray-200);
}

.link-preview {
    color: var(--color-primary);
    text-decoration: none;
    font-size: var(--font-size-sm);
}

.link-preview:hover {
    text-decoration: underline;
}

.table-actions {
    display: flex;
    gap: var(--spacing-2);
}

.btn-icon {
    margin-right: var(--spacing-1);
}

.btn-danger {
    background-color: #dc2626 !important;
    color: white !important;
    border: 1px solid #dc2626 !important;
}

.btn-danger:hover {
    background-color: #991b1b !important;
    border-color: #991b1b !important;
    color: white !important;
}

.admin-card-footer {
    padding: var(--spacing-4);
    border-top: 1px solid var(--color-gray-200);
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
