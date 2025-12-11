<?php
/**
 * Admin Edit Module Page
 * Form for editing an existing module
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

// Require admin authentication and solutions.manage permission
require_admin();
require_permission('solutions.manage');

// Initialize services
$contentService = new ContentService();
$sanitizationService = new InputSanitizationService();
$csrfMiddleware = new CsrfMiddleware();

// Get module ID from query parameter
$module_id = $_GET['id'] ?? '';

if (empty($module_id)) {
    $_SESSION['flash_message'] = 'Module ID is required.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/admin/modules.php');
    exit;
}

// Fetch existing module
$module = $contentService->read('module', $module_id);

if (!$module) {
    $_SESSION['flash_message'] = 'Module not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/admin/modules.php');
    exit;
}

// Initialize variables
$errors = [];
$form_data = [
    'name' => $module['name'],
    'slug' => $module['slug'],
    'description' => $module['description'] ?? '',
    'features' => $module['features'] ?? [],
    'screenshots' => $module['screenshots'] ?? [],
    'faqs' => $module['faqs'] ?? [],
    'display_order' => $module['display_order'] ?? 0,
    'status' => $module['status'] ?? 'DRAFT'
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
        $form_data['display_order'] = sanitizeInt($_POST['display_order'] ?? 0);
        $form_data['status'] = sanitizeString($_POST['status'] ?? 'DRAFT');
        
        // Validate required fields
        if (empty($form_data['name'])) {
            $errors[] = 'Module name is required.';
        }
        
        if (empty($form_data['slug'])) {
            $errors[] = 'Module slug is required.';
        }
        
        // Validate status
        if (!in_array($form_data['status'], ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
            $errors[] = 'Invalid status value.';
        }
        
        // Parse features (one per line)
        if (!empty($_POST['features'])) {
            $features_raw = explode("\n", $_POST['features']);
            $form_data['features'] = array_filter(array_map(function($feature) {
                return trim(sanitizeString($feature));
            }, $features_raw));
        } else {
            $form_data['features'] = [];
        }
        
        // Parse screenshots (one URL per line)
        if (!empty($_POST['screenshots'])) {
            $screenshots_raw = explode("\n", $_POST['screenshots']);
            $form_data['screenshots'] = array_filter(array_map(function($url) {
                return trim(sanitizeString($url));
            }, $screenshots_raw));
        } else {
            $form_data['screenshots'] = [];
        }
        
        // Parse FAQs (JSON format)
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
        
        // If no errors, update the module
        if (empty($errors)) {
            $result = $contentService->update('module', $module_id, $form_data);
            
            if ($result) {
                $_SESSION['flash_message'] = 'Module updated successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: ' . get_app_base_url() . '/admin/modules.php');
                exit;
            } else {
                $errors[] = 'Failed to update module. Please check if the slug is unique.';
            }
        }
    }
}

// Generate CSRF token
$csrf_token = getCsrfToken();

// Include admin header
include_admin_header('Edit Module');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Edit Module</h1>
        <p class="admin-page-description">Update module information</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/modules.php" class="btn btn-secondary">
            ← Back to Modules
        </a>
        <button type="button" class="btn btn-danger" onclick="confirmDelete()">
            Delete Module
        </button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Error:</strong>
        <ul class="alert-list">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo get_app_base_url(); ?>/admin/modules/edit.php?id=<?php echo urlencode($module_id); ?>" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Basic Information</h3>
        </div>
        
        <div class="admin-form-grid">
            <div class="admin-form-group">
                <label for="name" class="admin-form-label required">Module Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="admin-form-input" 
                    value="<?php echo htmlspecialchars($form_data['name']); ?>"
                    required
                    maxlength="255"
                >
                <p class="admin-form-help">The display name of the module</p>
            </div>
            
            <div class="admin-form-group">
                <label for="slug" class="admin-form-label required">Slug</label>
                <input 
                    type="text" 
                    id="slug" 
                    name="slug" 
                    class="admin-form-input" 
                    value="<?php echo htmlspecialchars($form_data['slug']); ?>"
                    required
                    pattern="[a-z0-9\-]+"
                    maxlength="255"
                >
                <p class="admin-form-help">URL-friendly identifier</p>
            </div>
            
            <div class="admin-form-group admin-form-group-full">
                <label for="description" class="admin-form-label">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="admin-form-textarea" 
                    rows="4"
                ><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                <p class="admin-form-help">Brief description of the module</p>
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
                <p class="admin-form-help">Only published modules appear on the website</p>
            </div>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Features</h3>
        </div>
        
        <div class="admin-form-group">
            <label for="features" class="admin-form-label">Key Features</label>
            <textarea 
                id="features" 
                name="features" 
                class="admin-form-textarea" 
                rows="6"
                placeholder="Enter one feature per line"
            ><?php echo htmlspecialchars(implode("\n", $form_data['features'])); ?></textarea>
            <p class="admin-form-help">Enter one feature per line</p>
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
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">FAQs</h3>
        </div>
        
        <div class="admin-form-group">
            <label for="faqs" class="admin-form-label">FAQs (JSON)</label>
            <textarea 
                id="faqs" 
                name="faqs" 
                class="admin-form-textarea" 
                rows="8"
                placeholder='[{"question": "What is this?", "answer": "This is..."}]'
            ><?php echo htmlspecialchars(json_encode($form_data['faqs'], JSON_PRETTY_PRINT)); ?></textarea>
            <p class="admin-form-help">Enter FAQs in JSON format: [{"question": "...", "answer": "..."}]</p>
        </div>
    </div>
    
    <div class="admin-form-actions">
        <button type="submit" class="btn btn-primary">Update Module</button>
        <a href="<?php echo get_app_base_url(); ?>/admin/modules.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<!-- Delete Confirmation Form (hidden) -->
<form id="deleteForm" method="POST" action="<?php echo get_app_base_url(); ?>/admin/modules/delete.php" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($module_id); ?>">
</form>

<style>
.admin-form {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.admin-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-4);
    padding: var(--spacing-4);
}

.admin-form-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-2);
}

.admin-form-group-full {
    grid-column: 1 / -1;
}

.admin-form-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
}

.admin-form-label.required::after {
    content: ' *';
    color: var(--color-danger);
}

.admin-form-input,
.admin-form-select,
.admin-form-textarea {
    padding: var(--spacing-2) var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    font-family: inherit;
}

.admin-form-textarea {
    resize: vertical;
    font-family: 'Courier New', monospace;
}

.admin-form-input:focus,
.admin-form-select:focus,
.admin-form-textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.admin-form-help {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
}

.admin-form-actions {
    display: flex;
    gap: var(--spacing-3);
    padding: var(--spacing-4);
    background: white;
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
}

.alert-list {
    margin: var(--spacing-2) 0 0 var(--spacing-4);
    padding: 0;
}

.alert-list li {
    margin-bottom: var(--spacing-1);
}

@media (max-width: 768px) {
    .admin-form-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this module? This action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include_admin_footer(); ?>
