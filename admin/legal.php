<?php
/**
 * Admin Legal Pages Management
 * Manage Terms of Service, Privacy Policy, and Refund Policy
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

use Karyalay\Models\Setting;
use Karyalay\Services\CsrfService;

// Start secure session
startSecureSession();

// Require admin authentication and legal.manage permission
require_admin();
require_permission('legal.manage');

// Initialize models
$settingModel = new Setting();
$csrfService = new CsrfService();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        try {
            // Get form data
            $terms_content = $_POST['legal_terms_of_service'] ?? '';
            $privacy_content = $_POST['legal_privacy_policy'] ?? '';
            $refund_content = $_POST['legal_refund_policy'] ?? '';
            
            // Save settings
            $settingModel->set('legal_terms_of_service', $terms_content);
            $settingModel->set('legal_terms_updated', date('F d, Y'));
            
            $settingModel->set('legal_privacy_policy', $privacy_content);
            $settingModel->set('legal_privacy_updated', date('F d, Y'));
            
            $settingModel->set('legal_refund_policy', $refund_content);
            $settingModel->set('legal_refund_updated', date('F d, Y'));
            
            $success_message = 'Legal pages updated successfully!';
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Fetch current settings
$settings = $settingModel->getMultiple([
    'legal_terms_of_service',
    'legal_privacy_policy',
    'legal_refund_policy'
]);

$terms_content = $settings['legal_terms_of_service'] ?? '';
$privacy_content = $settings['legal_privacy_policy'] ?? '';
$refund_content = $settings['legal_refund_policy'] ?? '';

// Generate CSRF token
$csrf_token = getCsrfToken();

// Include admin header
include_admin_header('Legal Pages');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Legal Pages</h1>
        <p class="admin-page-description">Manage Terms of Service, Privacy Policy, and Refund Policy</p>
    </div>
</div>

<?php $base_url = get_app_base_url(); ?>
<!-- Quick Preview Links -->
<div class="legal-preview-links">
    <a href="<?php echo $base_url; ?>/terms.php" class="legal-preview-link" target="_blank">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
        </svg>
        View Terms
    </a>
    <a href="<?php echo $base_url; ?>/privacy.php" class="legal-preview-link" target="_blank">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
        </svg>
        View Privacy
    </a>
    <a href="<?php echo $base_url; ?>/refund.php" class="legal-preview-link" target="_blank">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
        </svg>
        View Refund
    </a>
</div>

<!-- Success/Error Messages -->
<?php if ($success_message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<!-- Legal Pages Form -->
<div class="admin-card">
    <form method="POST" action="<?php echo $base_url; ?>/admin/legal.php" class="admin-form" id="legalForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <input type="hidden" id="termsInput" name="legal_terms_of_service">
        <input type="hidden" id="privacyInput" name="legal_privacy_policy">
        <input type="hidden" id="refundInput" name="legal_refund_policy">
        
        <!-- Terms of Service -->
        <div class="form-section">
            <h3 class="form-section-title">Terms of Service</h3>
            <p class="form-section-description">Define the terms and conditions for using your services. Use the rich text editor for formatting.</p>
            
            <div class="form-group">
                <label class="form-label">Terms Content</label>
                <div id="editor-terms-container">
                    <div id="editor-terms"></div>
                </div>
            </div>
        </div>
        
        <!-- Privacy Policy -->
        <div class="form-section">
            <h3 class="form-section-title">Privacy Policy</h3>
            <p class="form-section-description">Explain how you collect, use, and protect user data. Use the rich text editor for formatting.</p>
            
            <div class="form-group">
                <label class="form-label">Privacy Content</label>
                <div id="editor-privacy-container">
                    <div id="editor-privacy"></div>
                </div>
            </div>
        </div>
        
        <!-- Refund Policy -->
        <div class="form-section">
            <h3 class="form-section-title">Refund Policy</h3>
            <p class="form-section-description">Outline your refund and cancellation policies. Use the rich text editor for formatting.</p>
            
            <div class="form-group">
                <label class="form-label">Refund Content</label>
                <div id="editor-refund-container">
                    <div id="editor-refund"></div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Save Legal Pages
            </button>
            <a href="<?php echo get_app_base_url(); ?>/admin/dashboard.php" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<style>
/* Page Header */
.admin-page-header {
    margin-bottom: 20px;
}

.admin-page-header-content {
    max-width: 100%;
}

.admin-page-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 8px;
}

.admin-page-description {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

/* Preview Links */
.legal-preview-links {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.legal-preview-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    color: #4b5563;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.legal-preview-link:hover {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.legal-preview-link svg {
    flex-shrink: 0;
}

/* Form Sections */
.form-section {
    padding: 24px;
    margin-bottom: 24px;
}

.form-section:last-of-type {
    margin-bottom: 0;
}

.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-section-description {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 20px;
    line-height: 1.5;
}

/* Editor Containers */
#editor-terms-container,
#editor-privacy-container,
#editor-refund-container {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

#editor-terms,
#editor-privacy,
#editor-refund {
    min-height: 400px;
    font-size: 15px;
    line-height: 1.7;
}

/* Quill Editor Styling */
.ql-toolbar {
    border: none !important;
    border-bottom: 1px solid #e5e7eb !important;
    background: #f9fafb;
    padding: 12px !important;
}

.ql-container {
    border: none !important;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.ql-editor {
    padding: 24px;
}

.ql-editor p,
.ql-editor h1,
.ql-editor h2,
.ql-editor h3 {
    margin-bottom: 1em;
}

.ql-editor h2 {
    font-size: 1.5em;
    font-weight: 600;
    color: #1a202c;
}

.ql-editor h3 {
    font-size: 1.25em;
    font-weight: 600;
    color: #374151;
}

.ql-editor ul,
.ql-editor ol {
    margin-bottom: 1em;
    padding-left: 1.5em;
}

.ql-editor li {
    margin-bottom: 0.5em;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 12px;
    padding: 20px 24px;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    border-radius: 0 0 8px 8px;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    border: 1px solid transparent;
    cursor: pointer;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
}

.btn-secondary {
    background: white;
    color: #6b7280;
    border-color: #d1d5db;
}

.btn-secondary:hover {
    background: #f9fafb;
    color: #374151;
}

/* Alert Messages */
.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 14px;
}

.alert-success {
    background: #d1fae5;
    border: 1px solid #6ee7b7;
    color: #065f46;
}

.alert-danger {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

/* Admin Card */
.admin-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

/* Responsive */
@media (max-width: 768px) {
    .legal-preview-links {
        flex-direction: column;
    }
    
    .legal-preview-link {
        width: 100%;
        justify-content: center;
    }
    
    .form-section {
        padding: 16px;
    }
    
    .form-actions {
        flex-direction: column;
        padding: 16px;
    }
    
    .btn {
        width: 100%;
    }
    
    #editor-terms,
    #editor-privacy,
    #editor-refund {
        min-height: 300px;
    }
}
</style>

<!-- Quill JS -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toolbar configuration
    var toolbarOptions = [
        [{ 'header': [1, 2, 3, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        ['blockquote', 'code-block'],
        ['link'],
        [{ 'align': [] }],
        ['clean']
    ];
    
    // Initialize Terms of Service editor
    var quillTerms = new Quill('#editor-terms', {
        theme: 'snow',
        placeholder: 'Write your Terms of Service content here...',
        modules: {
            toolbar: toolbarOptions
        }
    });
    
    // Initialize Privacy Policy editor
    var quillPrivacy = new Quill('#editor-privacy', {
        theme: 'snow',
        placeholder: 'Write your Privacy Policy content here...',
        modules: {
            toolbar: toolbarOptions
        }
    });
    
    // Initialize Refund Policy editor
    var quillRefund = new Quill('#editor-refund', {
        theme: 'snow',
        placeholder: 'Write your Refund Policy content here...',
        modules: {
            toolbar: toolbarOptions
        }
    });
    
    // Load existing content
    <?php if (!empty($terms_content)): ?>
    quillTerms.root.innerHTML = <?php echo json_encode($terms_content); ?>;
    <?php endif; ?>
    
    <?php if (!empty($privacy_content)): ?>
    quillPrivacy.root.innerHTML = <?php echo json_encode($privacy_content); ?>;
    <?php endif; ?>
    
    <?php if (!empty($refund_content)): ?>
    quillRefund.root.innerHTML = <?php echo json_encode($refund_content); ?>;
    <?php endif; ?>
    
    // Sync editor content to hidden inputs on form submit
    document.getElementById('legalForm').addEventListener('submit', function() {
        document.getElementById('termsInput').value = quillTerms.root.innerHTML;
        document.getElementById('privacyInput').value = quillPrivacy.root.innerHTML;
        document.getElementById('refundInput').value = quillRefund.root.innerHTML;
    });
});
</script>

<?php include_admin_footer(); ?>
