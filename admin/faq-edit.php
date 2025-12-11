<?php
/**
 * FAQ Edit/Create - Admin Panel
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

$faqId = $_GET['id'] ?? null;
$faq = null;
$isEdit = false;

if ($faqId) {
    $faq = $faqModel->findById($faqId);
    if (!$faq) {
        $_SESSION['flash_message'] = 'FAQ not found.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . get_app_base_url() . '/admin/faqs.php');
        exit;
    }
    $isEdit = true;
}

$success = null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid security token.');
        }

        $question = trim($_POST['question'] ?? '');
        $answer = trim($_POST['answer'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $customCategory = trim($_POST['custom_category'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $status = $_POST['status'] ?? 'DRAFT';

        // Use custom category if provided
        if ($category === '__custom__' && !empty($customCategory)) {
            $category = $customCategory;
        }

        if (empty($question)) throw new Exception('Question is required.');
        if (empty($answer)) throw new Exception('Answer is required.');
        if (empty($category)) throw new Exception('Category is required.');

        $data = [
            'question' => $question,
            'answer' => $answer,
            'category' => $category,
            'display_order' => $displayOrder,
            'status' => $status
        ];

        if ($isEdit) {
            if ($faqModel->update($faqId, $data)) {
                $success = 'FAQ updated successfully!';
                $faq = $faqModel->findById($faqId);
            } else {
                throw new Exception('Failed to update FAQ.');
            }
        } else {
            $result = $faqModel->create($data);
            if ($result) {
                $_SESSION['flash_message'] = 'FAQ created successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: ' . get_app_base_url() . '/admin/faqs.php');
                exit;
            } else {
                throw new Exception('Failed to create FAQ.');
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Get existing categories for dropdown
$existingCategories = $faqModel->getCategories();
// Add default categories if empty
if (empty($existingCategories)) {
    $existingCategories = ['General Questions', 'Pricing & Plans', 'Features & Functionality', 'Support & Training'];
}

$page_title = $isEdit ? 'Edit FAQ' : 'Add New FAQ';
include __DIR__ . '/../templates/admin-header.php';
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title"><?php echo $isEdit ? 'Edit FAQ' : 'Add New FAQ'; ?></h1>
        <p class="admin-page-description"><?php echo $isEdit ? 'Update the FAQ details' : 'Create a new frequently asked question'; ?></p>
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

<div class="admin-card">
    <div class="card-body">
        <form method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-group">
                    <label for="question" class="form-label">Question *</label>
                    <input type="text" id="question" name="question" class="form-input" required
                           value="<?php echo htmlspecialchars($faq['question'] ?? ''); ?>"
                           placeholder="Enter the frequently asked question">
                </div>

                <div class="form-group">
                    <label for="answer" class="form-label">Answer *</label>
                    <textarea id="answer" name="answer" class="form-input" rows="6" required
                              placeholder="Enter the answer to this question"><?php echo htmlspecialchars($faq['answer'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="category" class="form-label">Category *</label>
                        <select id="category" name="category" class="form-input" required>
                            <option value="">Select a category</option>
                            <?php foreach ($existingCategories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo (isset($faq['category']) && $faq['category'] === $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="__custom__">+ Add Custom Category</option>
                        </select>
                    </div>

                    <div class="form-group form-group-half" id="custom-category-group" style="display: none;">
                        <label for="custom_category" class="form-label">Custom Category Name</label>
                        <input type="text" id="custom_category" name="custom_category" class="form-input"
                               placeholder="Enter new category name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" id="display_order" name="display_order" class="form-input"
                               value="<?php echo htmlspecialchars($faq['display_order'] ?? '0'); ?>"
                               min="0" placeholder="0">
                        <span class="form-help">Lower numbers appear first within the category</span>
                    </div>

                    <div class="form-group form-group-half">
                        <label for="status" class="form-label">Status *</label>
                        <select id="status" name="status" class="form-input" required>
                            <option value="DRAFT" <?php echo (isset($faq['status']) && $faq['status'] === 'DRAFT') ? 'selected' : ''; ?>>Draft</option>
                            <option value="PUBLISHED" <?php echo (isset($faq['status']) && $faq['status'] === 'PUBLISHED') ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isEdit ? 'Update FAQ' : 'Create FAQ'; ?>
                    </button>
                    <a href="<?php echo get_app_base_url(); ?>/admin/faqs.php" class="btn btn-secondary">Cancel</a>
                </div>
        </form>
    </div>
</div>

<style>
.admin-page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--spacing-6);gap:var(--spacing-4)}
.admin-page-header-content{flex:1}
.admin-page-title{font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-2) 0}
.admin-page-description{font-size:var(--font-size-base);color:var(--color-gray-600);margin:0}
.admin-page-header-actions{display:flex;gap:var(--spacing-3)}
.card-body{padding:var(--spacing-5)}
@media(max-width:768px){.admin-page-header{flex-direction:column}}
</style>

<script>
document.getElementById('category').addEventListener('change', function() {
    const customGroup = document.getElementById('custom-category-group');
    const customInput = document.getElementById('custom_category');
    
    if (this.value === '__custom__') {
        customGroup.style.display = 'block';
        customInput.required = true;
    } else {
        customGroup.style.display = 'none';
        customInput.required = false;
        customInput.value = '';
    }
});
</script>

<?php include __DIR__ . '/../templates/admin-footer.php'; ?>
