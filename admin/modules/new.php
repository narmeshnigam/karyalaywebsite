<?php
/**
 * Admin Create Module Page
 * Form for creating a new module
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\ContentService;
use Karyalay\Services\InputSanitizationService;
use Karyalay\Middleware\CsrfMiddleware;

startSecureSession();
require_admin();
require_permission('solutions.manage');

$contentService = new ContentService();
$sanitizationService = new InputSanitizationService();
$csrfMiddleware = new CsrfMiddleware();

$errors = [];
$form_data = [
    'name' => '',
    'slug' => '',
    'description' => '',
    'features' => [],
    'screenshots' => [],
    'faqs' => [],
    'display_order' => 0,
    'status' => 'DRAFT'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $form_data['name'] = sanitizeString($_POST['name'] ?? '');
        $form_data['slug'] = sanitizeString($_POST['slug'] ?? '');
        $form_data['description'] = sanitizeString($_POST['description'] ?? '');
        $form_data['display_order'] = sanitizeInt($_POST['display_order'] ?? 0);
        $form_data['status'] = sanitizeString($_POST['status'] ?? 'DRAFT');
        
        if (empty($form_data['name'])) {
            $errors[] = 'Module name is required.';
        }
        
        if (!in_array($form_data['status'], ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
            $errors[] = 'Invalid status value.';
        }
        
        if (!empty($_POST['features'])) {
            $features_raw = explode("\n", $_POST['features']);
            $form_data['features'] = array_filter(array_map(function($feature) use ($sanitizationService) {
                return trim($sanitizationService->sanitizeString($feature));
            }, $features_raw));
        }
        
        if (!empty($_POST['screenshots'])) {
            $screenshots_raw = explode("\n", $_POST['screenshots']);
            $form_data['screenshots'] = array_filter(array_map(function($url) use ($sanitizationService) {
                return trim($sanitizationService->sanitizeUrl($url));
            }, $screenshots_raw));
        }
        
        if (!empty($_POST['faqs'])) {
            $faqs_decoded = json_decode($_POST['faqs'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($faqs_decoded)) {
                $form_data['faqs'] = $faqs_decoded;
            } else {
                $errors[] = 'FAQs must be valid JSON format.';
            }
        }
        
        if (empty($errors)) {
            $result = $contentService->create('module', $form_data);
            
            if ($result) {
                $_SESSION['admin_success'] = 'Module created successfully!';
                header('Location: ' . get_app_base_url() . '/admin/modules.php');
                exit;
            } else {
                $errors[] = 'Failed to create module. Please check if the slug is unique.';
            }
        }
    }
}

$csrf_token = getCsrfToken();

include_admin_header('Create Module');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <nav class="admin-breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/modules.php">Modules</a>
            <span class="breadcrumb-separator">/</span>
            <span>Create Module</span>
        </nav>
        <h1 class="admin-page-title">Create New Module</h1>
        <p class="admin-page-description">Add a new module to display on the public website</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/modules.php" class="btn btn-secondary">
            ← Back to Modules
        </a>
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
    <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/modules/new.php" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-section">
            <h2 class="form-section-title">Basic Information</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label required">Module Name</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['name']); ?>"
                        required
                        maxlength="255"
                        placeholder="e.g., Inventory Management"
                    >
                    <p class="form-help">The display name of the module</p>
                </div>
                
                <div class="form-group">
                    <label for="slug" class="form-label">Slug</label>
                    <input 
                        type="text" 
                        id="slug" 
                        name="slug" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['slug']); ?>"
                        pattern="[a-z0-9\-]+"
                        maxlength="255"
                        placeholder="e.g., inventory-management"
                    >
                    <p class="form-help">URL-friendly identifier (auto-generated if left empty)</p>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="form-textarea" 
                    rows="4"
                    placeholder="Brief description of what this module does..."
                ><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                <p class="form-help">Brief description of the module</p>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="display_order" class="form-label">Display Order</label>
                    <input 
                        type="number" 
                        id="display_order" 
                        name="display_order" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['display_order']); ?>"
                        min="0"
                        placeholder="0"
                    >
                    <p class="form-help">Lower numbers appear first</p>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label required">Status</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="DRAFT" <?php echo $form_data['status'] === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                        <option value="PUBLISHED" <?php echo $form_data['status'] === 'PUBLISHED' ? 'selected' : ''; ?>>Published</option>
                        <option value="ARCHIVED" <?php echo $form_data['status'] === 'ARCHIVED' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                    <p class="form-help">Only published modules appear on the website</p>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Features</h2>
            
            <div class="form-group">
                <label for="features" class="form-label">Key Features</label>
                <textarea 
                    id="features" 
                    name="features" 
                    class="form-textarea" 
                    rows="6"
                    placeholder="Enter one feature per line, e.g.:&#10;Real-time inventory tracking&#10;Automated stock alerts&#10;Multi-warehouse support"
                ><?php echo htmlspecialchars(implode("\n", $form_data['features'])); ?></textarea>
                <p class="form-help">Enter one feature per line</p>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Media</h2>
            
            <div class="form-group">
                <label for="screenshots" class="form-label">Screenshots</label>
                <textarea 
                    id="screenshots" 
                    name="screenshots" 
                    class="form-textarea" 
                    rows="4"
                    placeholder="Enter one image URL per line, e.g.:&#10;https://example.com/screenshot1.png&#10;https://example.com/screenshot2.png"
                ><?php echo htmlspecialchars(implode("\n", $form_data['screenshots'])); ?></textarea>
                <p class="form-help">Enter one image URL per line</p>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">FAQs</h2>
            
            <div class="form-group">
                <label for="faqs" class="form-label">FAQs (JSON)</label>
                <textarea 
                    id="faqs" 
                    name="faqs" 
                    class="form-textarea form-textarea-code" 
                    rows="8"
                    placeholder='[&#10;  {"question": "What is this module?", "answer": "This module helps you..."},&#10;  {"question": "How do I get started?", "answer": "Simply navigate to..."}&#10;]'
                ><?php echo !empty($form_data['faqs']) ? htmlspecialchars(json_encode($form_data['faqs'], JSON_PRETTY_PRINT)) : ''; ?></textarea>
                <p class="form-help">Enter FAQs in JSON format: [{"question": "...", "answer": "..."}]</p>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Module</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/modules.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
.admin-breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    margin-bottom: 8px;
}
.admin-breadcrumb a {
    color: var(--color-primary);
    text-decoration: none;
}
.admin-breadcrumb a:hover {
    text-decoration: underline;
}
.breadcrumb-separator {
    color: var(--color-gray-400);
}
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    gap: 16px;
}
.admin-page-header-content {
    flex: 1;
}
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
    margin-bottom: 24px;
}
.alert-error {
    background-color: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}
.alert ul {
    margin: 8px 0 0 16px;
    padding: 0;
}
.admin-form {
    padding: 24px;
}
.form-section {
    margin-bottom: 32px;
}
.form-section:last-of-type {
    margin-bottom: 0;
}
.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-gray-900);
    margin: 0 0 16px 0;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--color-gray-200);
}
.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}
.form-group {
    margin-bottom: 16px;
}
.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--color-gray-700);
    margin-bottom: 8px;
}
.form-label.required::after {
    content: ' *';
    color: #dc2626;
}
.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--color-gray-300);
    border-radius: 6px;
    font-size: 14px;
    color: var(--color-gray-900);
    font-family: inherit;
    box-sizing: border-box;
}
.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
.form-textarea {
    resize: vertical;
}
.form-textarea-code {
    font-family: 'Courier New', Consolas, monospace;
    font-size: 13px;
}
.form-help {
    font-size: 12px;
    color: var(--color-gray-500);
    margin: 4px 0 0 0;
}
.form-actions {
    display: flex;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid var(--color-gray-200);
}
@media (max-width: 768px) {
    .admin-page-header {
        flex-direction: column;
    }
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.getElementById('name').addEventListener('input', function() {
    const slugInput = document.getElementById('slug');
    if (!slugInput.value || slugInput.dataset.autoGenerated === 'true') {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s\-]/g, '')
            .replace(/[\s_]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-+|-+$/g, '');
        slugInput.value = slug;
        slugInput.dataset.autoGenerated = 'true';
    }
});

document.getElementById('slug').addEventListener('input', function() {
    if (this.value) {
        this.dataset.autoGenerated = 'false';
    }
});
</script>

<?php include_admin_footer(); ?>
