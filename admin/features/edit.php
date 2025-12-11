<?php
/**
 * Admin Edit Feature Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\ContentService;

startSecureSession();
require_admin();
require_permission('content.edit');

$contentService = new ContentService();

$feature_id = $_GET['id'] ?? '';

if (empty($feature_id)) {
    $_SESSION['admin_error'] = 'Feature ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/features.php');
    exit;
}

$feature = $contentService->read('feature', $feature_id);

if (!$feature) {
    $_SESSION['admin_error'] = 'Feature not found.';
    header('Location: ' . get_app_base_url() . '/admin/features.php');
    exit;
}

$errors = [];

// Ensure JSON fields are arrays
$benefits = $feature['benefits'] ?? [];
if (is_string($benefits)) {
    $benefits = json_decode($benefits, true) ?? [];
}

$related_solutions = $feature['related_solutions'] ?? [];
if (is_string($related_solutions)) {
    $related_solutions = json_decode($related_solutions, true) ?? [];
}

$screenshots = $feature['screenshots'] ?? [];
if (is_string($screenshots)) {
    $screenshots = json_decode($screenshots, true) ?? [];
}

$form_data = [
    'name' => $feature['name'],
    'slug' => $feature['slug'],
    'description' => $feature['description'] ?? '',
    'icon_image' => $feature['icon_image'] ?? '',
    'benefits' => $benefits,
    'related_solutions' => $related_solutions,
    'screenshots' => $screenshots,
    'display_order' => $feature['display_order'] ?? 0,
    'status' => $feature['status'] ?? 'DRAFT'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $form_data['name'] = sanitizeString($_POST['name'] ?? '');
        $form_data['slug'] = sanitizeString($_POST['slug'] ?? '');
        $form_data['description'] = sanitizeString($_POST['description'] ?? '');
        $form_data['icon_image'] = sanitizeString($_POST['icon_image'] ?? '');
        $form_data['display_order'] = sanitizeInt($_POST['display_order'] ?? 0);
        $form_data['status'] = sanitizeString($_POST['status'] ?? 'DRAFT');
        
        if (empty($form_data['name'])) {
            $errors[] = 'Feature name is required.';
        }
        
        if (empty($form_data['slug'])) {
            $errors[] = 'Feature slug is required.';
        }
        
        if (!in_array($form_data['status'], ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
            $errors[] = 'Invalid status value.';
        }
        
        if (!empty($_POST['benefits'])) {
            $benefits_raw = explode("\n", $_POST['benefits']);
            $form_data['benefits'] = array_filter(array_map(function($benefit) {
                return trim(sanitizeString($benefit));
            }, $benefits_raw));
        } else {
            $form_data['benefits'] = [];
        }
        
        if (!empty($_POST['related_solutions'])) {
            $solutions_raw = explode("\n", $_POST['related_solutions']);
            $form_data['related_solutions'] = array_filter(array_map(function($solution) {
                return trim(sanitizeString($solution));
            }, $solutions_raw));
        } else {
            $form_data['related_solutions'] = [];
        }
        
        if (!empty($_POST['screenshots'])) {
            $screenshots_raw = explode("\n", $_POST['screenshots']);
            $form_data['screenshots'] = array_filter(array_map(function($url) {
                return trim(sanitizeString($url));
            }, $screenshots_raw));
        } else {
            $form_data['screenshots'] = [];
        }
        
        if (empty($errors)) {
            $result = $contentService->update('feature', $feature_id, $form_data);
            
            if ($result) {
                $_SESSION['admin_success'] = 'Feature updated successfully!';
                header('Location: ' . get_app_base_url() . '/admin/features.php');
                exit;
            } else {
                $errors[] = 'Failed to update feature. Please check if the slug is unique.';
            }
        }
    }
}

$csrf_token = getCsrfToken();
include_admin_header('Edit Feature');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <nav class="admin-breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/features.php">Features</a>
            <span class="breadcrumb-separator">/</span>
            <span>Edit Feature</span>
        </nav>
        <h1 class="admin-page-title">Edit Feature</h1>
        <p class="admin-page-description">Update feature information</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/features.php" class="btn btn-secondary">← Back to Features</a>
        <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Feature</button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>Error:</strong>
        <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/features/edit.php?id=<?php echo urlencode($feature_id); ?>" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-section">
            <h2 class="form-section-title">Basic Information</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label required">Feature Name</label>
                    <input type="text" id="name" name="name" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['name']); ?>" 
                        required maxlength="255" placeholder="e.g., Advanced Reporting">
                    <p class="form-help">The display name of the feature</p>
                </div>
                
                <div class="form-group">
                    <label for="slug" class="form-label required">Slug</label>
                    <input type="text" id="slug" name="slug" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['slug']); ?>" 
                        required pattern="[a-z0-9\-]+" maxlength="255" placeholder="e.g., advanced-reporting">
                    <p class="form-help">URL-friendly identifier</p>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-textarea" rows="4"
                    placeholder="Brief description of what this feature does..."><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                <p class="form-help">Brief description of the feature</p>
            </div>
            
            <div class="form-group">
                <label for="icon_image" class="form-label">Icon Image</label>
                <div class="image-input-group">
                    <input type="text" id="icon_image" name="icon_image" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['icon_image']); ?>"
                        placeholder="https://example.com/icon.png">
                    <?php if (!empty($form_data['icon_image'])): ?>
                        <div class="image-preview">
                            <img src="<?php echo htmlspecialchars($form_data['icon_image']); ?>" alt="Icon preview">
                        </div>
                    <?php endif; ?>
                </div>
                <p class="form-help">URL to a PNG icon image (recommended: 64x64 or 128x128 pixels)</p>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="display_order" class="form-label">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['display_order']); ?>" min="0" placeholder="0">
                    <p class="form-help">Lower numbers appear first</p>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label required">Status</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="DRAFT" <?php echo $form_data['status'] === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                        <option value="PUBLISHED" <?php echo $form_data['status'] === 'PUBLISHED' ? 'selected' : ''; ?>>Published</option>
                        <option value="ARCHIVED" <?php echo $form_data['status'] === 'ARCHIVED' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                    <p class="form-help">Only published features appear on the website</p>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Benefits</h2>
            <div class="form-group">
                <label for="benefits" class="form-label">Key Benefits</label>
                <textarea id="benefits" name="benefits" class="form-textarea" rows="6"
                    placeholder="Enter one benefit per line, e.g.:&#10;Increased productivity&#10;Better decision making&#10;Cost savings"><?php echo htmlspecialchars(is_array($form_data['benefits']) ? implode("\n", $form_data['benefits']) : ''); ?></textarea>
                <p class="form-help">Enter one benefit per line</p>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Related Solutions</h2>
            <div class="form-group">
                <label for="related_solutions" class="form-label">Related Solution IDs</label>
                <textarea id="related_solutions" name="related_solutions" class="form-textarea" rows="4"
                    placeholder="Enter one solution ID per line"><?php echo htmlspecialchars(is_array($form_data['related_solutions']) ? implode("\n", $form_data['related_solutions']) : ''); ?></textarea>
                <p class="form-help">Enter one solution ID per line</p>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Media</h2>
            <div class="form-group">
                <label for="screenshots" class="form-label">Screenshots</label>
                <textarea id="screenshots" name="screenshots" class="form-textarea" rows="4"
                    placeholder="Enter one image URL per line"><?php echo htmlspecialchars(is_array($form_data['screenshots']) ? implode("\n", $form_data['screenshots']) : ''); ?></textarea>
                <p class="form-help">Enter one image URL per line</p>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Feature</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/features.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<form id="deleteForm" method="POST" action="<?php echo get_app_base_url(); ?>/admin/features/delete.php" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($feature_id); ?>">
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
.admin-form { padding: 24px; }
.form-section { margin-bottom: 32px; }
.form-section:last-of-type { margin-bottom: 0; }
.form-section-title { font-size: 18px; font-weight: 600; color: var(--color-gray-900); margin: 0 0 16px 0; padding-bottom: 12px; border-bottom: 1px solid var(--color-gray-200); }
.form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 14px; font-weight: 600; color: var(--color-gray-700); margin-bottom: 8px; }
.form-label.required::after { content: ' *'; color: #dc2626; }
.form-input, .form-select, .form-textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--color-gray-300); border-radius: 6px; font-size: 14px; color: var(--color-gray-900); font-family: inherit; box-sizing: border-box; }
.form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.form-textarea { resize: vertical; }
.form-help { font-size: 12px; color: var(--color-gray-500); margin: 4px 0 0 0; }
.form-actions { display: flex; gap: 12px; padding-top: 24px; border-top: 1px solid var(--color-gray-200); }
.image-input-group { display: flex; flex-direction: column; gap: 12px; }
.image-preview { width: 80px; height: 80px; border: 1px solid var(--color-gray-300); border-radius: 8px; overflow: hidden; background: var(--color-gray-50); display: flex; align-items: center; justify-content: center; }
.image-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
@media (max-width: 768px) { .admin-page-header { flex-direction: column; } .form-row { grid-template-columns: 1fr; } }
</style>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this feature? This action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include_admin_footer(); ?>
