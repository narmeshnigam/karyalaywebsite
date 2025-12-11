<?php
/**
 * FAQ Management - Admin Panel
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Faq;
use Karyalay\Models\FaqCategory;

startSecureSession();
require_admin();
require_permission('faqs.manage');

$faqModel = new Faq();
$categoryModel = new FaqCategory();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (isset($_GET['csrf']) && $_GET['csrf'] === $_SESSION['csrf_token']) {
        if ($faqModel->delete($_GET['id'])) {
            $_SESSION['flash_message'] = 'FAQ deleted successfully.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to delete FAQ.';
            $_SESSION['flash_type'] = 'danger';
        }
    }
    header('Location: ' . get_app_base_url() . '/admin/faqs.php');
    exit;
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Get filters
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$filters = [];
if ($statusFilter) $filters['status'] = $statusFilter;
if ($categoryFilter) $filters['category'] = $categoryFilter;

$faqs = $faqModel->findAll($filters);
$categories = $faqModel->getCategories();

$page_title = 'FAQ Management';
include __DIR__ . '/../templates/admin-header.php';
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">FAQ Management</h1>
        <p class="admin-page-description">Manage frequently asked questions displayed on the website</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/faq-categories.php" class="btn btn-secondary">
            Manage Categories
        </a>
        <a href="<?php echo get_app_base_url(); ?>/admin/faq-edit.php" class="btn btn-primary">
            + Add New FAQ
        </a>
    </div>
</div>

<!-- Filters -->
<div class="admin-filters-section">
    <form method="GET" class="admin-filters-form">
        <div class="admin-filter-group">
            <label for="status" class="admin-filter-label">Status</label>
            <select name="status" id="status" class="admin-filter-select">
                <option value="">All Status</option>
                <option value="PUBLISHED" <?php echo $statusFilter === 'PUBLISHED' ? 'selected' : ''; ?>>Published</option>
                <option value="DRAFT" <?php echo $statusFilter === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
            </select>
        </div>
        <div class="admin-filter-group">
            <label for="category" class="admin-filter-label">Category</label>
            <select name="category" id="category" class="admin-filter-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-actions">
            <button type="submit" class="btn btn-secondary">Apply Filters</button>
            <?php if ($statusFilter || $categoryFilter): ?>
                <a href="<?php echo get_app_base_url(); ?>/admin/faqs.php" class="btn btn-text">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- FAQ List -->
<div class="admin-card">
    <?php if (empty($faqs)): ?>
        <?php 
        render_empty_state(
            'No FAQs found',
            'Create your first FAQ to get started',
            get_app_base_url() . '/admin/faq-edit.php',
            'Add New FAQ'
        );
        ?>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Category</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($faqs as $faq): ?>
                                <tr>
                                    <td>
                                        <div class="faq-question-cell">
                                            <?php echo htmlspecialchars(substr($faq['question'], 0, 80)); ?>
                                            <?php if (strlen($faq['question']) > 80): ?>...<?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary"><?php echo htmlspecialchars($faq['category']); ?></span>
                                    </td>
                                    <td><?php echo $faq['display_order']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $faq['status'] === 'PUBLISHED' ? 'success' : 'warning'; ?>">
                                            <?php echo $faq['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="<?php echo get_app_base_url(); ?>/admin/faq-edit.php?id=<?php echo urlencode($faq['id']); ?>" 
                                               class="btn btn-sm btn-secondary">Edit</a>
                                            <a href="<?php echo get_app_base_url(); ?>/admin/faqs.php?action=delete&id=<?php echo urlencode($faq['id']); ?>&csrf=<?php echo $_SESSION['csrf_token']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this FAQ?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
        </div>
    <?php endif; ?>
</div>

<style>
.admin-page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--spacing-6);gap:var(--spacing-4)}
.admin-page-header-content{flex:1}
.admin-page-title{font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-2) 0}
.admin-page-description{font-size:var(--font-size-base);color:var(--color-gray-600);margin:0}
.admin-page-header-actions{display:flex;gap:var(--spacing-3)}
.admin-filters-section{background:white;border:1px solid var(--color-gray-200);border-radius:var(--radius-lg);padding:var(--spacing-4);margin-bottom:var(--spacing-6)}
.admin-filters-form{display:flex;gap:var(--spacing-4);align-items:flex-end;flex-wrap:wrap}
.admin-filter-group{display:flex;flex-direction:column;gap:var(--spacing-2);flex:1;min-width:180px}
.admin-filter-label{font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);color:var(--color-gray-700)}
.admin-filter-select{padding:var(--spacing-2) var(--spacing-3);border:1px solid var(--color-gray-300);border-radius:var(--radius-md);font-size:var(--font-size-base);color:var(--color-gray-900)}
.admin-filter-select:focus{outline:none;border-color:var(--color-primary);box-shadow:0 0 0 3px rgba(59,130,246,0.1)}
.admin-filter-actions{display:flex;gap:var(--spacing-2)}
.faq-question-cell{max-width:400px;font-weight:500}
.table-actions{display:flex;gap:var(--spacing-2)}
.btn-danger{background-color:#dc2626 !important;color:white !important;border:1px solid #dc2626 !important}
.btn-danger:hover{background-color:#991b1b !important;border-color:#991b1b !important;color:white !important}
@media(max-width:768px){.admin-page-header{flex-direction:column}.admin-filters-form{flex-direction:column}.admin-filter-group{width:100%}}
</style>

<?php include __DIR__ . '/../templates/admin-footer.php'; ?>
