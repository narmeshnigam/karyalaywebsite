<?php
/**
 * Admin Edit Case Study Page
 * Form for editing an existing case study
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

use Karyalay\Services\ContentService;

// Start secure session
startSecureSession();

// Require admin authentication and case_studies.manage permission
require_admin();
require_permission('case_studies.manage');

// Initialize services
$contentService = new ContentService();

// Get case study ID from query parameter
$case_study_id = $_GET['id'] ?? '';

if (empty($case_study_id)) {
    $_SESSION['flash_message'] = 'Case study ID is required.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/admin/case-studies.php');
    exit;
}

// Fetch existing case study
$case_study = $contentService->read('case_study', $case_study_id);

if (!$case_study) {
    $_SESSION['flash_message'] = 'Case study not found.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . get_app_base_url() . '/admin/case-studies.php');
    exit;
}

// Initialize variables
$errors = [];
$form_data = [
    'title' => $case_study['title'],
    'slug' => $case_study['slug'],
    'client_name' => $case_study['client_name'],
    'industry' => $case_study['industry'] ?? '',
    'challenge' => $case_study['challenge'] ?? '',
    'cover_image' => $case_study['cover_image'] ?? '',
    'solution' => $case_study['solution'] ?? '',
    'results' => $case_study['results'] ?? '',
    'modules_used' => $case_study['modules_used'] ?? [],
    'status' => $case_study['status'] ?? 'DRAFT',
    'is_featured' => $case_study['is_featured'] ?? false
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate input
        $form_data['title'] = sanitizeString($_POST['title'] ?? '');
        $form_data['slug'] = sanitizeString($_POST['slug'] ?? '');
        $form_data['client_name'] = sanitizeString($_POST['client_name'] ?? '');
        $form_data['industry'] = sanitizeString($_POST['industry'] ?? '');
        $form_data['challenge'] = sanitizeString($_POST['challenge'] ?? '');
        $form_data['cover_image'] = sanitizeString($_POST['cover_image'] ?? '');
        $form_data['solution'] = sanitizeString($_POST['solution'] ?? '');
        $form_data['results'] = sanitizeString($_POST['results'] ?? '');
        $form_data['status'] = sanitizeString($_POST['status'] ?? 'DRAFT');
        $form_data['is_featured'] = isset($_POST['is_featured']) ? true : false;
        
        // Validate required fields
        if (empty($form_data['title'])) {
            $errors[] = 'Case study title is required.';
        }
        
        if (empty($form_data['slug'])) {
            $errors[] = 'Case study slug is required.';
        }
        
        if (empty($form_data['client_name'])) {
            $errors[] = 'Client name is required.';
        }
        
        if (empty($form_data['challenge'])) {
            $errors[] = 'Challenge description is required.';
        }
        
        if (empty($form_data['solution'])) {
            $errors[] = 'Solution description is required.';
        }
        
        if (empty($form_data['results'])) {
            $errors[] = 'Results description is required.';
        }
        
        // Validate status
        if (!in_array($form_data['status'], ['DRAFT', 'PUBLISHED', 'ARCHIVED'])) {
            $errors[] = 'Invalid status value.';
        }
        
        // Parse modules used (comma-separated)
        if (!empty($_POST['modules_used'])) {
            $modules_raw = explode(',', $_POST['modules_used']);
            $form_data['modules_used'] = array_filter(array_map(function($module) {
                return trim(sanitizeString($module));
            }, $modules_raw));
        } else {
            $form_data['modules_used'] = [];
        }
        
        // If no errors, update the case study
        if (empty($errors)) {
            $result = $contentService->update('case_study', $case_study_id, $form_data);
            
            if ($result) {
                $_SESSION['flash_message'] = 'Case study updated successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: ' . get_app_base_url() . '/admin/case-studies.php');
                exit;
            } else {
                $errors[] = 'Failed to update case study. Please check if the slug is unique.';
            }
        }
    }
}

// Generate CSRF token
$csrf_token = getCsrfToken();

// Include admin header
include_admin_header('Edit Case Study');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Edit Case Study</h1>
        <p class="admin-page-description">Update case study information</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/case-studies.php" class="btn btn-secondary">
            ← Back to Case Studies
        </a>
        <button type="button" class="btn btn-danger" onclick="confirmDelete()">
            Delete Case Study
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

<form method="POST" action="<?php echo get_app_base_url(); ?>/admin/case-studies/edit.php?id=<?php echo urlencode($case_study_id); ?>" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Basic Information</h3>
        </div>
        
        <div class="admin-form-grid">
            <div class="admin-form-group admin-form-group-full">
                <label for="title" class="admin-form-label required">Case Study Title</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    class="admin-form-input" 
                    value="<?php echo htmlspecialchars($form_data['title']); ?>"
                    required
                    maxlength="255"
                >
                <p class="admin-form-help">The main title of the case study</p>
            </div>
            
            <div class="admin-form-group admin-form-group-full">
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
            
            <div class="admin-form-group">
                <label for="client_name" class="admin-form-label required">Client Name</label>
                <input 
                    type="text" 
                    id="client_name" 
                    name="client_name" 
                    class="admin-form-input" 
                    value="<?php echo htmlspecialchars($form_data['client_name']); ?>"
                    required
                    maxlength="255"
                >
                <p class="admin-form-help">Name of the client organization</p>
            </div>
            
            <div class="admin-form-group">
                <label for="industry" class="admin-form-label">Industry</label>
                <input 
                    type="text" 
                    id="industry" 
                    name="industry" 
                    class="admin-form-input" 
                    value="<?php echo htmlspecialchars($form_data['industry']); ?>"
                    maxlength="255"
                >
                <p class="admin-form-help">Client's industry sector</p>
            </div>
            
            <div class="admin-form-group">
                <label for="modules_used" class="admin-form-label">Modules Used</label>
                <input 
                    type="text" 
                    id="modules_used" 
                    name="modules_used" 
                    class="admin-form-input" 
                    value="<?php echo htmlspecialchars(implode(', ', $form_data['modules_used'])); ?>"
                >
                <p class="admin-form-help">Comma-separated list of modules</p>
            </div>
            
            <div class="admin-form-group">
                <label for="status" class="admin-form-label required">Status</label>
                <select id="status" name="status" class="admin-form-select" required>
                    <option value="DRAFT" <?php echo $form_data['status'] === 'DRAFT' ? 'selected' : ''; ?>>Draft</option>
                    <option value="PUBLISHED" <?php echo $form_data['status'] === 'PUBLISHED' ? 'selected' : ''; ?>>Published</option>
                    <option value="ARCHIVED" <?php echo $form_data['status'] === 'ARCHIVED' ? 'selected' : ''; ?>>Archived</option>
                </select>
                <p class="admin-form-help">Only published case studies appear on the website</p>
            </div>
            
            <div class="admin-form-group admin-form-group-full">
                <div class="form-checkbox-group">
                    <label class="form-checkbox-label">
                        <input type="checkbox" id="is_featured" name="is_featured" 
                               class="form-checkbox" value="1"
                               <?php echo !empty($form_data['is_featured']) ? 'checked' : ''; ?>>
                        <span class="form-checkbox-text">
                            <strong>Feature on Homepage</strong>
                            <span class="form-checkbox-help">Display this case study in the featured section on the homepage</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Case Study Details</h3>
            <p class="admin-card-description">Describe the challenge, solution, and results</p>
        </div>
        
        <div class="admin-form-grid">
            <div class="admin-form-group admin-form-group-full">
                <label for="challenge" class="admin-form-label required">Challenge</label>
                <textarea 
                    id="challenge" 
                    name="challenge" 
                    class="admin-form-textarea" 
                    rows="6"
                    required
                ><?php echo htmlspecialchars($form_data['challenge']); ?></textarea>
                <p class="admin-form-help">What problem was the client trying to solve?</p>
            </div>
            
            <div class="admin-form-group admin-form-group-full">
                <label for="cover_image" class="admin-form-label">Cover Image URL</label>
                <input 
                    type="text" 
                    id="cover_image" 
                    name="cover_image" 
                    class="admin-form-input" 
                    value="<?php echo htmlspecialchars($form_data['cover_image']); ?>"
                    placeholder="https://example.com/case-study-cover.jpg"
                >
                <p class="admin-form-help">URL to cover image for homepage display (recommended: 16:9 aspect ratio)</p>
            </div>
            
            <div class="admin-form-group admin-form-group-full">
                <label for="solution" class="admin-form-label required">Solution</label>
                <textarea 
                    id="solution" 
                    name="solution" 
                    class="admin-form-textarea" 
                    rows="8"
                    required
                ><?php echo htmlspecialchars($form_data['solution']); ?></textarea>
                <p class="admin-form-help">How did Karyalay solve the problem?</p>
            </div>
            
            <div class="admin-form-group admin-form-group-full">
                <label for="results" class="admin-form-label required">Results</label>
                <textarea 
                    id="results" 
                    name="results" 
                    class="admin-form-textarea" 
                    rows="6"
                    required
                ><?php echo htmlspecialchars($form_data['results']); ?></textarea>
                <p class="admin-form-help">What were the measurable outcomes and benefits?</p>
            </div>
        </div>
    </div>
    
    <div class="admin-form-actions">
        <button type="submit" class="btn btn-primary">Update Case Study</button>
        <a href="<?php echo get_app_base_url(); ?>/admin/case-studies.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<!-- Delete Confirmation Form (hidden) -->
<form id="deleteForm" method="POST" action="<?php echo get_app_base_url(); ?>/admin/case-studies/delete.php" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($case_study_id); ?>">
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
    line-height: 1.6;
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

.admin-card-description {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: var(--spacing-1) 0 0 0;
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
    if (confirm('Are you sure you want to delete this case study? This action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include_admin_footer(); ?>
