<?php
/**
 * Admin Create Case Study Page
 * Form for creating a new case study
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

// Initialize variables
$errors = [];
$form_data = [
    'title' => '',
    'slug' => '',
    'client_name' => '',
    'industry' => '',
    'challenge' => '',
    'cover_image' => '',
    'solution' => '',
    'results' => '',
    'modules_used' => [],
    'status' => 'DRAFT',
    'is_featured' => false
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
        }
        
        // If no errors, create the case study
        if (empty($errors)) {
            $result = $contentService->create('case_study', $form_data);
            
            if ($result) {
                $_SESSION['flash_message'] = 'Case study created successfully!';
                $_SESSION['flash_type'] = 'success';
                header('Location: ' . get_app_base_url() . '/admin/case-studies.php');
                exit;
            } else {
                $errors[] = 'Failed to create case study. Please check if the slug is unique.';
            }
        }
    }
}

// Generate CSRF token
$csrf_token = getCsrfToken();

// Include admin header
include_admin_header('Create Case Study');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Create New Case Study</h1>
        <p class="admin-page-description">Document a client success story</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/case-studies.php" class="btn btn-secondary">
            ← Back to Case Studies
        </a>
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

<form method="POST" action="<?php echo get_app_base_url(); ?>/admin/case-studies/new.php" class="admin-form">
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
                    placeholder="How Company X Achieved Success with Karyalay"
                >
                <p class="admin-form-help">The main title of the case study</p>
            </div>
            
            <div class="admin-form-group admin-form-group-full">
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
                    placeholder="Acme Corporation"
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
                    placeholder="Healthcare, Finance, Education, etc."
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
                    placeholder="Inventory Management, CRM, Accounting"
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
                               <?php echo $form_data['is_featured'] ? 'checked' : ''; ?>>
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
                    placeholder="Describe the problem or challenge the client was facing..."
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
                    placeholder="Explain how Karyalay was implemented to address the challenge..."
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
                    placeholder="Share the measurable outcomes and benefits achieved..."
                ><?php echo htmlspecialchars($form_data['results']); ?></textarea>
                <p class="admin-form-help">What were the measurable outcomes and benefits?</p>
            </div>
        </div>
    </div>
    
    <div class="admin-form-actions">
        <button type="submit" class="btn btn-primary">Create Case Study</button>
        <a href="<?php echo get_app_base_url(); ?>/admin/case-studies.php" class="btn btn-secondary">Cancel</a>
    </div>
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
// Auto-generate slug from title
document.getElementById('title').addEventListener('input', function() {
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
