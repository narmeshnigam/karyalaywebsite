<?php
/**
 * Admin Create Why Choose Card Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Models\WhyChooseCard;

startSecureSession();
require_admin();
require_permission('why_choose.manage');

$cardModel = new WhyChooseCard();

$errors = [];
$form_data = [
    'title' => '',
    'description' => '',
    'image_url' => '',
    'link_url' => '',
    'display_order' => 0,
    'status' => 'DRAFT'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $form_data['title'] = sanitizeString($_POST['title'] ?? '');
        $form_data['description'] = sanitizeString($_POST['description'] ?? '');
        $form_data['image_url'] = sanitizeString($_POST['image_url'] ?? '');
        $form_data['link_url'] = sanitizeString($_POST['link_url'] ?? '');
        $form_data['display_order'] = sanitizeInt($_POST['display_order'] ?? 0);
        $form_data['status'] = sanitizeString($_POST['status'] ?? 'DRAFT');

        if (empty($form_data['title'])) {
            $errors[] = 'Title is required.';
        }
        if (empty($form_data['image_url'])) {
            $errors[] = 'Image URL is required.';
        }
        if (!in_array($form_data['status'], ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
            $errors[] = 'Invalid status value.';
        }

        if (empty($errors)) {
            $result = $cardModel->create($form_data);
            if ($result) {
                $_SESSION['admin_success'] = 'Card created successfully!';
                header('Location: ' . get_app_base_url() . '/admin/why-choose-cards.php');
                exit;
            } else {
                $errors[] = 'Failed to create card.';
            }
        }
    }
}

$csrf_token = getCsrfToken();
include_admin_header('Create Why Choose Card');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <nav class="admin-breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/why-choose-cards.php">Why Choose Cards</a>
            <span class="breadcrumb-separator">/</span>
            <span>Create Card</span>
        </nav>
        <h1 class="admin-page-title">Create New Card</h1>
        <p class="admin-page-description">Add a new card to the "Why Choose Karyalay" section</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/why-choose-cards.php" class="btn btn-secondary">← Back to Cards</a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>Error:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/why-choose-cards/new.php" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-section">
            <h2 class="form-section-title">Card Content</h2>
            
            <div class="form-group">
                <label for="title" class="form-label required">Title</label>
                <input type="text" id="title" name="title" class="form-input" 
                       value="<?php echo htmlspecialchars($form_data['title']); ?>"
                       required maxlength="255" placeholder="e.g., Modular Design">
                <p class="form-help">Main heading displayed on the card</p>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-textarea" rows="3"
                          placeholder="Brief description of this feature..."><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                <p class="form-help">Short description displayed below the title</p>
            </div>
            
            <div class="form-group">
                <label for="image_url" class="form-label required">Image URL (16:9 ratio recommended)</label>
                <input type="url" id="image_url" name="image_url" class="form-input" 
                       value="<?php echo htmlspecialchars($form_data['image_url']); ?>"
                       required maxlength="500" placeholder="https://example.com/image.jpg">
                <p class="form-help">Recommended size: 640x360px or any 16:9 aspect ratio</p>
            </div>
            
            <div class="form-group">
                <label for="link_url" class="form-label">Link URL (Optional)</label>
                <input type="url" id="link_url" name="link_url" class="form-input" 
                       value="<?php echo htmlspecialchars($form_data['link_url']); ?>"
                       maxlength="500" placeholder="https://example.com/page">
                <p class="form-help">URL to navigate when card is clicked</p>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Display Settings</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="display_order" class="form-label">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-input" 
                           value="<?php echo htmlspecialchars($form_data['display_order']); ?>"
                           min="0" placeholder="0">
                    <p class="form-help">Lower numbers appear first (max 6 cards displayed)</p>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label required">Status</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="DRAFT" <?php echo $form_data['status'] === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                        <option value="PUBLISHED" <?php echo $form_data['status'] === 'PUBLISHED' ? 'selected' : ''; ?>>Published</option>
                        <option value="ARCHIVED" <?php echo $form_data['status'] === 'ARCHIVED' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                    <p class="form-help">Only published cards appear on the website</p>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Card</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/why-choose-cards.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
.admin-breadcrumb { display: flex; align-items: center; gap: var(--spacing-2); font-size: var(--font-size-sm); margin-bottom: var(--spacing-2); }
.admin-breadcrumb a { color: var(--color-primary); text-decoration: none; }
.admin-breadcrumb a:hover { text-decoration: underline; }
.breadcrumb-separator { color: var(--color-gray-400); }
.admin-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: var(--spacing-6); gap: var(--spacing-4); }
.admin-page-header-content { flex: 1; }
.admin-page-title { font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); color: var(--color-gray-900); margin: 0 0 var(--spacing-2) 0; }
.admin-page-description { font-size: var(--font-size-base); color: var(--color-gray-600); margin: 0; }
.admin-page-header-actions { display: flex; gap: var(--spacing-3); }
.alert { padding: var(--spacing-4); border-radius: var(--radius-lg); margin-bottom: var(--spacing-6); }
.alert-error { background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
.alert ul { margin: var(--spacing-2) 0 0 var(--spacing-4); padding: 0; }
.admin-form { padding: var(--spacing-6); }
.form-section { margin-bottom: var(--spacing-8); }
.form-section:last-of-type { margin-bottom: 0; }
.form-section-title { font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold); color: var(--color-gray-900); margin: 0 0 var(--spacing-4) 0; padding-bottom: var(--spacing-3); border-bottom: 1px solid var(--color-gray-200); }
.form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--spacing-4); }
.form-group { margin-bottom: var(--spacing-4); }
.form-label { display: block; font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold); color: var(--color-gray-700); margin-bottom: var(--spacing-2); }
.form-label.required::after { content: ' *'; color: #dc2626; }
.form-input, .form-select, .form-textarea { width: 100%; padding: var(--spacing-2) var(--spacing-3); border: 1px solid var(--color-gray-300); border-radius: var(--radius-md); font-size: var(--font-size-sm); color: var(--color-gray-900); font-family: inherit; box-sizing: border-box; }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.form-textarea { resize: vertical; }
.form-help { font-size: var(--font-size-xs); color: var(--color-gray-500); margin: var(--spacing-1) 0 0 0; }
.form-actions { display: flex; gap: var(--spacing-3); padding-top: var(--spacing-6); border-top: 1px solid var(--color-gray-200); }
@media (max-width: 768px) { .admin-page-header { flex-direction: column; } .form-row { grid-template-columns: 1fr; } }
</style>

<?php include_admin_footer(); ?>
