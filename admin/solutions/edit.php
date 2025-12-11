<?php
/**
 * Admin Edit Solution Page
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\ContentService;

startSecureSession();
require_admin();
require_permission('solutions.manage');

$contentService = new ContentService();

$solution_id = $_GET['id'] ?? '';

if (empty($solution_id)) {
    $_SESSION['admin_error'] = 'Solution ID is required.';
    header('Location: ' . get_app_base_url() . '/admin/solutions.php');
    exit;
}

$solution = $contentService->read('solution', $solution_id);

if (!$solution) {
    $_SESSION['admin_error'] = 'Solution not found.';
    header('Location: ' . get_app_base_url() . '/admin/solutions.php');
    exit;
}

$errors = [];
$form_data = [
    'name' => $solution['name'],
    'slug' => $solution['slug'],
    'description' => $solution['description'] ?? '',
    'icon_image' => $solution['icon_image'] ?? '',
    'benefits' => $solution['benefits'] ?? [],
    'features' => $solution['features'] ?? [],
    'screenshots' => $solution['screenshots'] ?? [],
    'faqs' => $solution['faqs'] ?? [],
    'display_order' => $solution['display_order'] ?? 0,
    'status' => $solution['status'] ?? 'DRAFT',
    'featured_on_homepage' => $solution['featured_on_homepage'] ?? false
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
        $form_data['featured_on_homepage'] = isset($_POST['featured_on_homepage']) ? true : false;
        
        if (empty($form_data['name'])) {
            $errors[] = 'Solution name is required.';
        }
        
        if (empty($form_data['slug'])) {
            $errors[] = 'Solution slug is required.';
        }
        
        if (!in_array($form_data['status'], ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
            $errors[] = 'Invalid status value.';
        }
        
        if (!empty($_POST['benefits'])) {
            $benefits_decoded = json_decode($_POST['benefits'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($benefits_decoded)) {
                $form_data['benefits'] = $benefits_decoded;
            } else {
                $errors[] = 'Benefits must be valid JSON format.';
            }
        } else {
            $form_data['benefits'] = [];
        }
        
        if (!empty($_POST['features'])) {
            $features_raw = explode("\n", $_POST['features']);
            $form_data['features'] = array_filter(array_map(function($feature) {
                return trim(sanitizeString($feature));
            }, $features_raw));
        } else {
            $form_data['features'] = [];
        }
        
        if (!empty($_POST['screenshots'])) {
            $screenshots_raw = explode("\n", $_POST['screenshots']);
            $form_data['screenshots'] = array_filter(array_map(function($url) {
                return trim(sanitizeString($url));
            }, $screenshots_raw));
        } else {
            $form_data['screenshots'] = [];
        }
        
        if (!empty($_POST['faqs'])) {
            $faqs_decoded = json_decode($_POST['faqs'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($faqs_decoded)) {
                $form_data['faqs'] = $faqs_decoded;
            } else {
                $errors[] = 'FAQs must be valid JSON format.';
            }
        } else {
            $form_data['faqs'] = [];
        }
        
        if (empty($errors)) {
            $result = $contentService->update('solution', $solution_id, $form_data);
            
            if ($result) {
                $_SESSION['admin_success'] = 'Solution updated successfully!';
                header('Location: ' . get_app_base_url() . '/admin/solutions.php');
                exit;
            } else {
                $errors[] = 'Failed to update solution. Please check if the slug is unique.';
            }
        }
    }
}

$csrf_token = getCsrfToken();
include_admin_header('Edit Solution');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <nav class="admin-breadcrumb">
            <a href="<?php echo get_app_base_url(); ?>/admin/solutions.php">Solutions</a>
            <span class="breadcrumb-separator">/</span>
            <span>Edit Solution</span>
        </nav>
        <h1 class="admin-page-title">Edit Solution</h1>
        <p class="admin-page-description">Update solution information</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/solutions.php" class="btn btn-secondary">← Back to Solutions</a>
        <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete Solution</button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <strong>Error:</strong>
        <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/solutions/edit.php?id=<?php echo urlencode($solution_id); ?>" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-section">
            <h2 class="form-section-title">Basic Information</h2>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label required">Solution Name</label>
                    <input type="text" id="name" name="name" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['name']); ?>" required maxlength="255">
                    <p class="form-help">The display name of the solution</p>
                </div>
                
                <div class="form-group">
                    <label for="slug" class="form-label required">Slug</label>
                    <input type="text" id="slug" name="slug" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['slug']); ?>" required pattern="[a-z0-9\-]+" maxlength="255">
                    <p class="form-help">URL-friendly identifier</p>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-textarea" rows="4"><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                <p class="form-help">Brief description of the solution</p>
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
                <p class="form-help">URL to a PNG icon image (recommended: 64x64 or 128x128 pixels). Displayed on homepage cards.</p>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="display_order" class="form-label">Display Order</label>
                    <input type="number" id="display_order" name="display_order" class="form-input" 
                        value="<?php echo htmlspecialchars($form_data['display_order']); ?>" min="0">
                    <p class="form-help">Lower numbers appear first</p>
                </div>
                
                <div class="form-group">
                    <label for="status" class="form-label required">Status</label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="DRAFT" <?php echo $form_data['status'] === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                        <option value="PUBLISHED" <?php echo $form_data['status'] === 'PUBLISHED' ? 'selected' : ''; ?>>Published</option>
                        <option value="ARCHIVED" <?php echo $form_data['status'] === 'ARCHIVED' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                    <p class="form-help">Only published solutions appear on the website</p>
                </div>
            </div>
            
            <div class="form-group">
                <div class="form-checkbox-group">
                    <label class="form-checkbox-label">
                        <input type="checkbox" id="featured_on_homepage" name="featured_on_homepage" 
                               class="form-checkbox" value="1"
                               <?php echo !empty($form_data['featured_on_homepage']) ? 'checked' : ''; ?>>
                        <span class="form-checkbox-text">
                            <strong>Feature on Homepage</strong>
                            <span class="form-checkbox-help">Display this solution in the "Powerful Solutions" section on the homepage</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Benefits</h2>
            <div class="form-group">
                <label for="benefits" class="form-label">Key Benefits (JSON)</label>
                <textarea id="benefits" name="benefits" class="form-textarea form-textarea-code" rows="8" 
                    placeholder='[{"title": "Benefit Title", "description": "Benefit description"}]'><?php echo htmlspecialchars(json_encode($form_data['benefits'], JSON_PRETTY_PRINT)); ?></textarea>
                <p class="form-help">Enter benefits in JSON format with title and description fields</p>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Features</h2>
            <div class="form-group">
                <label for="features" class="form-label">Key Features</label>
                <textarea id="features" name="features" class="form-textarea" rows="6" 
                    placeholder="Enter one feature per line"><?php echo htmlspecialchars(implode("\n", $form_data['features'])); ?></textarea>
                <p class="form-help">Enter one feature per line</p>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">Media</h2>
            <div class="form-group">
                <label for="screenshots" class="form-label">Screenshots</label>
                <textarea id="screenshots" name="screenshots" class="form-textarea" rows="4" 
                    placeholder="Enter one image URL per line"><?php echo htmlspecialchars(implode("\n", $form_data['screenshots'])); ?></textarea>
                <p class="form-help">Enter one image URL per line</p>
            </div>
        </div>
        
        <div class="form-section">
            <h2 class="form-section-title">FAQs</h2>
            <div class="form-group">
                <label for="faqs" class="form-label">FAQs (JSON)</label>
                <textarea id="faqs" name="faqs" class="form-textarea form-textarea-code" rows="8" 
                    placeholder='[{"question": "What is this?", "answer": "This is..."}]'><?php echo htmlspecialchars(json_encode($form_data['faqs'], JSON_PRETTY_PRINT)); ?></textarea>
                <p class="form-help">Enter FAQs in JSON format</p>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Solution</button>
            <a href="<?php echo get_app_base_url(); ?>/admin/solutions.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<form id="deleteForm" method="POST" action="<?php echo get_app_base_url(); ?>/admin/solutions/delete.php" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($solution_id); ?>">
</form>

<style>
.admin-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: 14px; margin-bottom: 8px; }
.admin-breadcrumb a { color: var(--color-primary); text-decoration: none; }
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
.form-textarea-code { font-family: 'Courier New', Consolas, monospace; font-size: 13px; }
.form-help { font-size: 12px; color: var(--color-gray-500); margin: 4px 0 0 0; }
.form-actions { display: flex; gap: 12px; padding-top: 24px; border-top: 1px solid var(--color-gray-200); }
.form-checkbox-group { padding: var(--spacing-4); background-color: var(--color-gray-50); border-radius: var(--radius-md); border: 1px solid var(--color-gray-200); }
.form-checkbox-label { display: flex; align-items: flex-start; gap: var(--spacing-3); cursor: pointer; }
.form-checkbox { width: 20px; height: 20px; margin-top: 2px; cursor: pointer; }
.form-checkbox-text { flex: 1; }
.form-checkbox-text strong { display: block; font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold); color: var(--color-gray-900); margin-bottom: var(--spacing-1); }
.form-checkbox-help { display: block; font-size: var(--font-size-xs); color: var(--color-gray-600); }
.image-input-group { display: flex; flex-direction: column; gap: 12px; }
.image-preview { width: 80px; height: 80px; border: 1px solid var(--color-gray-300); border-radius: 8px; overflow: hidden; background: var(--color-gray-50); display: flex; align-items: center; justify-content: center; }
.image-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
@media (max-width: 768px) { .admin-page-header { flex-direction: column; } .form-row { grid-template-columns: 1fr; } }
</style>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this solution? This action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include_admin_footer(); ?>
