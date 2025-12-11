<?php
/**
 * Admin Create Feature Page
 * Form for creating a new feature
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\ContentService;
use Karyalay\Services\InputSanitizationService;
use Karyalay\Middleware\CsrfMiddleware;

// Start secure session
startSecureSession();

// Require admin authentication and content.create permission
require_admin();
require_permission('content.create');

// Initialize services
$contentService = new ContentService();
$sanitizationService = new InputSanitizationService();
$csrfMiddleware = new CsrfMiddleware();

// Initialize variables
$errors = [];
$form_data = [
    'name' => '',
    'slug' => '',
    'description' => '',
    'icon_image' => '',
    'benefits' => [],
    'related_solutions' => [],
    'screenshots' => [],
    'display_order' => 0,
    'status' => 'DRAFT'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate input
        $form_data['name'] = sanitizeString($_POST['name'] ?? '');
        $form_data['slug'] = sanitizeString($_POST['slug'] ?? '');
        $form_data['description'] = sanitizeString($_POST['description'] ?? '');
        $form_data['icon_image'] = sanitizeString($_POST['icon_image'] ?? '');
        $form_data['display_order'] = intval($_POST['display_order'] ?? 0);
        $form_data['status'] = $_POST['status'] ?? 'DRAFT';
        
        // Validate required fields
        if (empty($form_data['name'])) {
            $errors[] = 'Feature name is required.';
        }
        
        // Validate status
        if (!in_array($form_data['status'], ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
            $errors[] = 'Invalid status value.';
        }
        
        // Parse benefits (one per line)
        if (!empty($_POST['benefits'])) {
            $benefits_raw = explode("\n", $_POST['benefits']);
            $form_data['benefits'] = array_filter(array_map(function($benefit)  {
                return trim(sanitizeString($benefit));
            }, $benefits_raw));
        }
        
        // Parse related solutions (one per line)
        if (!empty($_POST['related_solutions'])) {
            $solutions_raw = explode("\n", $_POST['related_solutions']);
            $form_data['related_solutions'] = array_filter(array_map(function($solution)  {
                return trim(sanitizeString($solution));
            }, $solutions_raw));
        }
        
        // Parse screenshots (one URL per line)
        if (!empty($_POST['screenshots'])) {
            $screenshots_raw = explode("\n", $_POST['screenshots']);
            $form_data['screenshots'] = array_filter(array_map(function($url)  {
                return trim(sanitizeString($url));
            }, $screenshots_raw));
        }
        
        // If no errors, create the feature
        if (empty($errors)) {
            $result = $contentService->create('feature', $form_data);
            
            if ($result) {
                $_SESSION['admin_success'] = 'Feature created successfully!';
                header('Location: ' . get_app_base_url() . '/admin/features.php');
                exit;
            } else {
                $errors[] = 'Failed to create feature. Please check if the slug is unique.';
            }
        }
    }
}

// Generate CSRF token
$csrf_token = getCsrfToken();

// Include admin header
include_admin_header('Create Feature');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <nav class="admin-breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/features.php">Features</a>
            <span class="breadcrumb-separator">/</span>
            <span>Create Feature</span>
        </nav>
        <h1 class="admin-page-title">Create New Feature</h1>
        <p class="admin-page-description">Add a new feature to display on the public website</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/features.php" class="btn btn-secondary">
            ← Back to Features
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

<form method="POST" action="<?php echo get_app_base_url(); ?>/admin/features/new.php" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Basic Information</h3>
        </div>
        
        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label for="name" class="admin-form-label required">Feature Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="admin-form-input" 
                    value="<?php echo htmlspecialchars($form_data['name']); ?>"
                    required
                    maxlength="255"
                >
                <p class="admin-form-help">The display name of the feature</p>
            </div>
            
            <div class="admin-form-group">
                <label for="slug" class="admin-form-label">Slug</label>
                <input 
                    type="text" 
                    id="slug" 
                    name="slug" 
                    class="admin-form-input" 
                    value="<?php echo htmlspecialchars($form_data['slug']); ?>"
                    pattern="[a-z0-9\-]+"
                    maxlength="255"
                >
                <p class="admin-form-help">URL-friendly identifier (auto-generated if left empty)</p>
            </div>
            
            <div class="admin-form-group admin-form-group-full">
                <label for="description" class="admin-form-label">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="admin-form-textarea" 
                    rows="4"
                ><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                <p class="admin-form-help">Brief description of the feature</p>
            </div>
            
            <div class="admin-form-group admin-form-group-full">
                <label for="icon_image" class="admin-form-label">Icon Image</label>
                <div class="image-input-group">
                    <input 
                        type="text" 
                        id="icon_image" 
                        name="icon_image" 
                        class="admin-form-input" 
                        value="<?php echo htmlspecialchars($form_data['icon_image']); ?>"
                        placeholder="https://example.com/icon.png"
                    >
                    <?php if (!empty($form_data['icon_image'])): ?>
                        <div class="image-preview">
                            <img src="<?php echo htmlspecialchars($form_data['icon_image']); ?>" alt="Icon preview">
                        </div>
                    <?php endif; ?>
                </div>
                <p class="admin-form-help">URL to a PNG icon image (recommended: 64x64 or 128x128 pixels)</p>
            </div>
            
            <div class="admin-form-group">
                <label for="display_order" class="admin-form-label">Display Order</label>
                <input 
                    type="number" 
                    id="display_order" 
                    name="display_order" 
                    class="admin-form-input" 
                    value="<?php echo htmlspecialchars($form_data['display_order']); ?>"
                    min="0"
                >
                <p class="admin-form-help">Lower numbers appear first</p>
            </div>
            
            <div class="admin-form-group">
                <label for="status" class="admin-form-label required">Status</label>
                <select id="status" name="status" class="admin-form-select" required>
                    <option value="DRAFT" <?php echo $form_data['status'] === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                    <option value="PUBLISHED" <?php echo $form_data['status'] === 'PUBLISHED' ? 'selected' : ''; ?>>Published</option>
                    <option value="ARCHIVED" <?php echo $form_data['status'] === 'ARCHIVED' ? 'selected' : ''; ?>>Archived</option>
                </select>
                <p class="admin-form-help">Only published features appear on the website</p>
            </div>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Benefits</h3>
        </div>
        
        <div class="admin-form-group">
            <label for="benefits" class="admin-form-label">Key Benefits</label>
            <textarea 
                id="benefits" 
                name="benefits" 
                class="admin-form-textarea" 
                rows="6"
                placeholder="Enter one benefit per line"
            ><?php echo htmlspecialchars(implode("\n", $form_data['benefits'])); ?></textarea>
            <p class="admin-form-help">Enter one benefit per line</p>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Related Solutions</h3>
        </div>
        
        <div class="admin-form-group">
            <label for="related_solutions" class="admin-form-label">Related Solution IDs</label>
            <textarea 
                id="related_solutions" 
                name="related_solutions" 
                class="admin-form-textarea" 
                rows="4"
                placeholder="Enter one solution ID per line"
            ><?php echo htmlspecialchars(implode("\n", $form_data['related_solutions'])); ?></textarea>
            <p class="admin-form-help">Enter one solution ID per line</p>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Media</h3>
        </div>
        
        <div class="admin-form-group">
            <label for="screenshots" class="admin-form-label">Screenshots</label>
            <textarea 
                id="screenshots" 
                name="screenshots" 
                class="admin-form-textarea" 
                rows="4"
                placeholder="Enter one image URL per line"
            ><?php echo htmlspecialchars(implode("\n", $form_data['screenshots'])); ?></textarea>
            <p class="admin-form-help">Enter one image URL per line</p>
        </div>
    </div>
    
    <div class="admin-form-actions">
        <button type="submit" class="btn btn-primary">Create Feature</button>
        <a href="<?php echo get_app_base_url(); ?>/admin/features.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<style>
.admin-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 14px; margin-bottom: 8px; }
.admin-breadcrumb a { color: var(--color-primary); text-decoration: none; }
.admin-breadcrumb a:hover { text-decoration: underline; }
.breadcrumb-separator { color: var(--color-gray-400); }
.admin-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 16px; }
.admin-page-header-content { flex: 1; }
.admin-page-title { font-size: 24px; font-weight: 700; color: var(--color-gray-900); margin: 0 0 8px 0; }
.admin-page-description { font-size: 14px; color: var(--color-gray-600); margin: 0; }
.admin-page-header-actions { display: flex; gap: 12px; }
.alert { padding: 16px; border-radius: 8px; margin-bottom: 24px; }
.alert-error { background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
.alert ul { margin: 8px 0 0 16px; padding: 0; }
.admin-form { display: flex; flex-direction: column; gap: 24px; }
.admin-form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; padding: 16px; }
.admin-form-group { display: flex; flex-direction: column; gap: 8px; }
.admin-form-group-full { grid-column: 1 / -1; }
.admin-form-label { font-size: 14px; font-weight: 600; color: var(--color-gray-700); }
.admin-form-label.required::after { content: ' *'; color: #dc2626; }
.admin-form-input, .admin-form-select, .admin-form-textarea { padding: 10px 12px; border: 1px solid var(--color-gray-300); border-radius: 6px; font-size: 14px; color: var(--color-gray-900); font-family: inherit; }
.admin-form-textarea { resize: vertical; font-family: 'Courier New', monospace; }
.admin-form-input:focus, .admin-form-select:focus, .admin-form-textarea:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.admin-form-help { font-size: 12px; color: var(--color-gray-500); margin: 0; }
.admin-form-actions { display: flex; gap: 12px; padding: 16px; background: white; border: 1px solid var(--color-gray-200); border-radius: 8px; }
.image-input-group { display: flex; flex-direction: column; gap: 12px; }
.image-preview { width: 80px; height: 80px; border: 1px solid var(--color-gray-300); border-radius: 8px; overflow: hidden; background: var(--color-gray-50); display: flex; align-items: center; justify-content: center; }
.image-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
@media (max-width: 768px) { .admin-page-header { flex-direction: column; } .admin-form-grid { grid-template-columns: 1fr; } .admin-form-actions { flex-direction: column; } }
</style>

<script>
// Auto-generate slug from name
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
