<?php
/**
 * FAQ Categories Management - Admin Panel
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\FaqCategory;

startSecureSession();
require_admin();
require_permission('faqs.manage');

$categoryModel = new FaqCategory();
$success = null;
$error = null;

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (isset($_GET['csrf']) && $_GET['csrf'] === $_SESSION['csrf_token']) {
        if ($categoryModel->delete($_GET['id'])) {
            $_SESSION['flash_message'] = 'Category deleted successfully.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to delete category.';
            $_SESSION['flash_type'] = 'danger';
        }
    }
    header('Location: ' . get_app_base_url() . '/admin/faq-categories.php');
    exit;
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid security token.');
        }

        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $status = $_POST['status'] ?? 'ACTIVE';
        $editId = $_POST['edit_id'] ?? null;

        if (empty($name)) throw new Exception('Category name is required.');

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));

        if ($categoryModel->slugExists($slug, $editId)) {
            throw new Exception('A category with this name already exists.');
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'display_order' => $displayOrder,
            'status' => $status
        ];

        if ($editId) {
            if ($categoryModel->update($editId, $data)) {
                $success = 'Category updated successfully!';
            } else {
                throw new Exception('Failed to update category.');
            }
        } else {
            if ($categoryModel->create($data)) {
                $success = 'Category created successfully!';
            } else {
                throw new Exception('Failed to create category.');
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$categories = $categoryModel->findAll();

$page_title = 'FAQ Categories';
include __DIR__ . '/../templates/admin-header.php';
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">FAQ Categories</h1>
        <p class="admin-page-description">Manage FAQ categories for organizing questions</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/faqs.php" class="btn btn-secondary">← Back to FAQs</a>
    </div>
</div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

<div class="admin-grid">
    <!-- Add/Edit Form -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title" id="form-title">Add New Category</h2>
        </div>
        <div class="card-body">
                <form method="POST" id="category-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="edit_id" id="edit_id" value="">

                    <div class="form-group">
                        <label for="name" class="form-label">Category Name *</label>
                        <input type="text" id="name" name="name" class="form-input" required placeholder="e.g., Billing Questions">
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" id="description" name="description" class="form-input" placeholder="Brief description">
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input type="number" id="display_order" name="display_order" class="form-input" value="0" min="0">
                        </div>
                        <div class="form-group form-group-half">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-input">
                                <option value="ACTIVE">Active</option>
                                <option value="INACTIVE">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submit-btn">Add Category</button>
                        <button type="button" class="btn btn-secondary" id="cancel-btn" style="display:none;" onclick="resetForm()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Categories List -->
    <div class="admin-card">
        <div class="card-header">
            <h2 class="card-title">Existing Categories</h2>
        </div>
        <div class="card-body">
                <?php if (empty($categories)): ?>
                    <p class="text-muted">No categories yet.</p>
                <?php else: ?>
                    <div class="categories-list">
                        <?php foreach ($categories as $cat): ?>
                            <div class="category-item">
                                <div class="category-info">
                                    <strong><?php echo htmlspecialchars($cat['name']); ?></strong>
                                    <span class="badge badge-<?php echo $cat['status'] === 'ACTIVE' ? 'success' : 'secondary'; ?>">
                                        <?php echo $cat['status']; ?>
                                    </span>
                                    <?php if ($cat['description']): ?>
                                        <p class="category-desc"><?php echo htmlspecialchars($cat['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="category-actions">
                                    <button type="button" class="btn btn-sm btn-secondary" 
                                            onclick="editCategory('<?php echo htmlspecialchars($cat['id']); ?>', '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>', '<?php echo htmlspecialchars(addslashes($cat['description'] ?? '')); ?>', <?php echo $cat['display_order']; ?>, '<?php echo $cat['status']; ?>')">
                                        Edit
                                    </button>
                                    <a href="?action=delete&id=<?php echo urlencode($cat['id']); ?>&csrf=<?php echo $_SESSION['csrf_token']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Delete this category? This will not delete the FAQs in this category.');">Delete</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.admin-page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--spacing-6);gap:var(--spacing-4)}
.admin-page-header-content{flex:1}
.admin-page-title{font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-2) 0}
.admin-page-description{font-size:var(--font-size-base);color:var(--color-gray-600);margin:0}
.admin-page-header-actions{display:flex;gap:var(--spacing-3)}
.alert{padding:var(--spacing-4);border-radius:var(--radius-md);margin-bottom:var(--spacing-4);display:flex;align-items:center;gap:var(--spacing-2)}
.alert-success{background-color:#f0fdf4;border:1px solid #86efac;color:#166534}
.alert-danger{background-color:#fef2f2;border:1px solid #fca5a5;color:#991b1b}
.card-header{padding:var(--spacing-5);border-bottom:1px solid var(--color-gray-200)}
.card-title{font-size:var(--font-size-lg);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin:0}
.card-body{padding:var(--spacing-5)}
.form-group{margin-bottom:var(--spacing-4)}
.form-label{display:block;font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);color:var(--color-gray-700);margin-bottom:var(--spacing-2)}
.form-input{width:100%;padding:var(--spacing-2) var(--spacing-3);border:1px solid var(--color-gray-300);border-radius:var(--radius-md);font-size:var(--font-size-base);color:var(--color-gray-900)}
.form-input:focus{outline:none;border-color:var(--color-primary);box-shadow:0 0 0 3px rgba(59,130,246,0.1)}
.form-row{display:flex;gap:var(--spacing-4)}
.form-group-half{flex:1}
.form-actions{display:flex;gap:var(--spacing-3);margin-top:var(--spacing-6);padding-top:var(--spacing-4);border-top:1px solid var(--color-gray-200)}
.admin-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:var(--spacing-6)}
.categories-list{display:flex;flex-direction:column;gap:1rem}
.category-item{display:flex;justify-content:space-between;align-items:center;padding:1rem;background:var(--color-gray-50);border-radius:var(--radius-lg);gap:var(--spacing-4)}
.category-info{flex:1}
.category-info strong{display:block;margin-bottom:var(--spacing-1)}
.category-desc{margin:0.25rem 0 0;font-size:0.875rem;color:var(--color-gray-500)}
.category-actions{display:flex;gap:0.5rem;flex-shrink:0}
.text-muted{color:var(--color-gray-500);font-style:italic}
.btn-danger{background-color:#dc2626 !important;color:white !important;border:1px solid #dc2626 !important}
.btn-danger:hover{background-color:#991b1b !important;border-color:#991b1b !important;color:white !important}
@media(max-width:768px){.admin-page-header{flex-direction:column}.admin-grid{grid-template-columns:1fr}.form-row{flex-direction:column}.category-item{flex-direction:column;align-items:flex-start}}
</style>

<script>
function editCategory(id, name, description, order, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('name').value = name;
    document.getElementById('description').value = description;
    document.getElementById('display_order').value = order;
    document.getElementById('status').value = status;
    document.getElementById('form-title').textContent = 'Edit Category';
    document.getElementById('submit-btn').textContent = 'Update Category';
    document.getElementById('cancel-btn').style.display = 'inline-block';
    document.getElementById('name').focus();
}

function resetForm() {
    document.getElementById('category-form').reset();
    document.getElementById('edit_id').value = '';
    document.getElementById('form-title').textContent = 'Add New Category';
    document.getElementById('submit-btn').textContent = 'Add Category';
    document.getElementById('cancel-btn').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../templates/admin-footer.php'; ?>
