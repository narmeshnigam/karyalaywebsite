<?php
/**
 * Admin Testimonials List Page
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Testimonial;

startSecureSession();
require_admin();
require_permission('testimonials.manage');

$testimonialModel = new Testimonial();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$filters = [];
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

try {
    $testimonials = $testimonialModel->getAll($filters, $limit, $offset);
} catch (Exception $e) {
    error_log('Testimonials list error: ' . $e->getMessage());
    $testimonials = [];
}

include_admin_header('Testimonials');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Testimonials</h1>
        <p class="admin-page-description">Manage customer testimonials and reviews</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/testimonials/new.php" class="btn btn-primary">
            + Add Testimonial
        </a>
    </div>
</div>

<?php if (isset($_SESSION['admin_success'])): ?>
    <div class="alert alert-success">
        <?php 
        echo htmlspecialchars($_SESSION['admin_success']); 
        unset($_SESSION['admin_success']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['admin_error'])): ?>
    <div class="alert alert-error">
        <?php 
        echo htmlspecialchars($_SESSION['admin_error']); 
        unset($_SESSION['admin_error']);
        ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-filters">
            <form method="GET" action="" class="filter-form">
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="DRAFT" <?php echo (isset($_GET['status']) && $_GET['status'] === 'DRAFT') ? 'selected' : ''; ?>>Draft</option>
                    <option value="PUBLISHED" <?php echo (isset($_GET['status']) && $_GET['status'] === 'PUBLISHED') ? 'selected' : ''; ?>>Published</option>
                    <option value="ARCHIVED" <?php echo (isset($_GET['status']) && $_GET['status'] === 'ARCHIVED') ? 'selected' : ''; ?>>Archived</option>
                </select>
            </form>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Company</th>
                    <th>Rating</th>
                    <th>Testimonial</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($testimonials)): ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <p class="empty-state">No testimonials found. <a href="<?php echo get_app_base_url(); ?>/admin/testimonials/new.php">Add your first testimonial</a></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($testimonials as $testimonial): ?>
                        <tr>
                            <td>
                                <div class="customer-info">
                                    <?php if (!empty($testimonial['customer_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($testimonial['customer_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($testimonial['customer_name']); ?>"
                                             class="customer-avatar">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo htmlspecialchars($testimonial['customer_name']); ?></strong>
                                        <?php if (!empty($testimonial['customer_title'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($testimonial['customer_title']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo !empty($testimonial['customer_company']) ? htmlspecialchars($testimonial['customer_company']) : '-'; ?></td>
                            <td>
                                <div class="rating-display">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $testimonial['rating'] ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td>
                                <div class="testimonial-preview">
                                    <?php echo htmlspecialchars(substr($testimonial['testimonial_text'], 0, 80)); ?>
                                    <?php echo strlen($testimonial['testimonial_text']) > 80 ? '...' : ''; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($testimonial['is_featured']): ?>
                                    <span class="badge badge-success">Featured</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $testimonial['status'] === 'PUBLISHED' ? 'success' : ($testimonial['status'] === 'DRAFT' ? 'warning' : 'secondary'); ?>">
                                    <?php echo htmlspecialchars($testimonial['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($testimonial['display_order']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?php echo get_app_base_url(); ?>/admin/testimonials/edit.php?id=<?php echo urlencode($testimonial['id']); ?>" 
                                       class="btn btn-sm btn-secondary">Edit</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.admin-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; }
.admin-page-header-content { flex: 1; }
.admin-page-title { font-size: 24px; font-weight: 700; color: var(--color-gray-900); margin: 0 0 8px 0; }
.admin-page-description { font-size: 14px; color: var(--color-gray-600); margin: 0; }
.admin-page-header-actions { display: flex; gap: 12px; }
.alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
.alert-success { background-color: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; }
.alert-error { background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
.admin-card-header { padding: 16px 24px; border-bottom: 1px solid var(--color-gray-200); }
.filter-form { display: flex; gap: 12px; }
.filter-select { padding: 8px 12px; border: 1px solid var(--color-gray-300); border-radius: 6px; font-size: 14px; }
.customer-info { display: flex; align-items: center; gap: 12px; }
.customer-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
.rating-display { display: flex; gap: 2px; }
.rating-display .star { color: #d1d5db; font-size: 16px; }
.rating-display .star.filled { color: #fbbf24; }
.testimonial-preview { max-width: 300px; }
.badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
.badge-success { background-color: #d1fae5; color: #065f46; }
.badge-warning { background-color: #fef3c7; color: #92400e; }
.badge-secondary { background-color: #f3f4f6; color: #6b7280; }
.action-buttons { display: flex; gap: 8px; }
.empty-state { padding: 40px 20px; color: var(--color-gray-500); }
.text-muted { color: var(--color-gray-500); }
@media (max-width: 768px) { .admin-page-header { flex-direction: column; } }
</style>

<?php include_admin_footer(); ?>
