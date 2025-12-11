<?php
/**
 * Admin SEO Settings Page
 * Manage default meta title, description, and OG image
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Models\Setting;
use Karyalay\Middleware\CsrfMiddleware;
use Karyalay\Services\MediaUploadService;

// Start secure session
startSecureSession();

// Require admin authentication and settings.general permission (ADMIN only)
require_admin();
require_permission('settings.general');

// Initialize services
$settingModel = new Setting();
$csrfMiddleware = new CsrfMiddleware();
$mediaUploadService = new MediaUploadService();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrfMiddleware->validate()) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        try {
            // Get form data
            $meta_title = trim($_POST['meta_title'] ?? '');
            $meta_description = trim($_POST['meta_description'] ?? '');
            
            // Validate required fields
            if (empty($meta_title)) {
                throw new Exception('Meta title is required');
            }
            
            if (empty($meta_description)) {
                throw new Exception('Meta description is required');
            }
            
            // Validate length constraints
            if (strlen($meta_title) > 60) {
                throw new Exception('Meta title should be 60 characters or less for optimal display');
            }
            
            if (strlen($meta_description) > 160) {
                throw new Exception('Meta description should be 160 characters or less for optimal display');
            }
            
            // Handle OG image upload
            if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] === UPLOAD_ERR_OK) {
                $og_image_result = $mediaUploadService->uploadFile(
                    $_FILES['og_image'],
                    ['image/jpeg', 'image/png'],
                    5 * 1024 * 1024 // 5MB max
                );
                
                if ($og_image_result['success']) {
                    $settingModel->set('og_image_url', $og_image_result['url']);
                } else {
                    throw new Exception('OG image upload failed: ' . $og_image_result['error']);
                }
            }
            
            // Save SEO settings
            $settingModel->set('meta_title', $meta_title);
            $settingModel->set('meta_description', $meta_description);
            
            $success_message = 'SEO settings saved successfully!';
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Fetch current settings
$settings = $settingModel->getMultiple([
    'meta_title',
    'meta_description',
    'og_image_url'
]);

// Set defaults if not found
$meta_title = $settings['meta_title'] ?? 'Karyalay - Business Management Platform';
$meta_description = $settings['meta_description'] ?? 'Comprehensive business management platform with subscription-based services';
$og_image_url = $settings['og_image_url'] ?? '';

// Generate CSRF token
$csrf_token = getCsrfToken();

// Include admin header
include_admin_header('SEO Settings');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">SEO Settings</h1>
        <p class="admin-page-description">Optimize your site for search engines and social media</p>
    </div>
</div>

<?php $base_url = get_app_base_url(); ?>
<!-- Settings Navigation -->
<div class="settings-nav">
    <a href="<?php echo $base_url; ?>/admin/settings/general.php" class="settings-nav-item">General</a>
    <a href="<?php echo $base_url; ?>/admin/settings/branding.php" class="settings-nav-item">Branding</a>
    <a href="<?php echo $base_url; ?>/admin/settings/seo.php" class="settings-nav-item active">SEO</a>
    <a href="<?php echo $base_url; ?>/admin/settings/legal-identity.php" class="settings-nav-item">Legal Identity</a>
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

<!-- SEO Settings Form -->
<div class="admin-card">
    <form method="POST" action="<?php echo get_app_base_url(); ?>/admin/settings/seo.php" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-section">
            <h3 class="form-section-title">Meta Tags</h3>
            <p class="form-section-description">
                These default values are used when specific pages don't have their own meta tags defined.
            </p>
            
            <div class="form-group">
                <label for="meta_title" class="form-label">
                    Default Meta Title <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="meta_title" 
                    name="meta_title" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($meta_title); ?>"
                    required
                    maxlength="60"
                >
                <div class="character-count">
                    <span id="meta_title_count"><?php echo strlen($meta_title); ?></span>/60 characters
                </div>
                <p class="form-help">
                    The title that appears in search engine results and browser tabs. Keep it under 60 characters.
                </p>
            </div>
            
            <div class="form-group">
                <label for="meta_description" class="form-label">
                    Default Meta Description <span class="required">*</span>
                </label>
                <textarea 
                    id="meta_description" 
                    name="meta_description" 
                    class="form-textarea" 
                    rows="3"
                    required
                    maxlength="160"
                ><?php echo htmlspecialchars($meta_description); ?></textarea>
                <div class="character-count">
                    <span id="meta_description_count"><?php echo strlen($meta_description); ?></span>/160 characters
                </div>
                <p class="form-help">
                    A brief description that appears in search results. Keep it under 160 characters.
                </p>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section-title">Social Media (Open Graph)</h3>
            <p class="form-section-description">
                Control how your site appears when shared on social media platforms like Facebook, Twitter, and LinkedIn.
            </p>
            
            <div class="form-group">
                <label for="og_image" class="form-label">
                    Default OG Image
                </label>
                
                <?php if ($og_image_url): ?>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($og_image_url); ?>" alt="Current OG image" class="preview-og-image">
                        <div class="preview-info">
                            <p class="preview-label">Current OG Image</p>
                            <p class="preview-dimensions">Recommended: 1200x630px</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <input 
                    type="file" 
                    id="og_image" 
                    name="og_image" 
                    class="form-input-file" 
                    accept="image/jpeg,image/png"
                >
                <p class="form-help">
                    Upload a default image for social media sharing (JPG or PNG, max 5MB). Recommended size: 1200x630px
                </p>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section-title">Preview</h3>
            <p class="form-section-description">
                See how your site might appear in search results and social media.
            </p>
            
            <div class="seo-preview">
                <div class="preview-search">
                    <h4 class="preview-heading">Search Engine Result</h4>
                    <div class="search-result">
                        <div class="search-title" id="preview_title">
                            <?php echo htmlspecialchars($meta_title); ?>
                        </div>
                        <div class="search-url">https://yoursite.com</div>
                        <div class="search-description" id="preview_description">
                            <?php echo htmlspecialchars($meta_description); ?>
                        </div>
                    </div>
                </div>
                
                <div class="preview-social">
                    <h4 class="preview-heading">Social Media Share</h4>
                    <div class="social-card">
                        <?php if ($og_image_url): ?>
                            <img src="<?php echo htmlspecialchars($og_image_url); ?>" alt="OG preview" class="social-image">
                        <?php else: ?>
                            <div class="social-image-placeholder">No image set</div>
                        <?php endif; ?>
                        <div class="social-content">
                            <div class="social-title" id="preview_social_title">
                                <?php echo htmlspecialchars($meta_title); ?>
                            </div>
                            <div class="social-description" id="preview_social_description">
                                <?php echo htmlspecialchars($meta_description); ?>
                            </div>
                            <div class="social-url">yoursite.com</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Save Settings
            </button>
            <a href="<?php echo get_app_base_url(); ?>/admin/dashboard.php" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<style>
.settings-nav {
    display: flex;
    gap: var(--spacing-2);
    margin-bottom: var(--spacing-6);
    border-bottom: 2px solid var(--color-gray-200);
}

.settings-nav-item {
    padding: var(--spacing-3) var(--spacing-4);
    color: var(--color-gray-600);
    text-decoration: none;
    font-weight: var(--font-weight-semibold);
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all var(--transition-fast);
}

.settings-nav-item:hover {
    color: var(--color-gray-900);
}

.settings-nav-item.active {
    color: var(--color-primary);
    border-bottom-color: var(--color-primary);
}

.admin-form {
    padding: var(--spacing-6);
}

.form-section {
    margin-bottom: var(--spacing-8);
}

.form-section:last-of-type {
    margin-bottom: var(--spacing-6);
}

.form-section-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
}

.form-section-description {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0 0 var(--spacing-4) 0;
    padding-bottom: var(--spacing-3);
    border-bottom: 1px solid var(--color-gray-200);
}

.form-group {
    margin-bottom: var(--spacing-5);
}

.form-label {
    display: block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-2);
}

.required {
    color: var(--color-danger);
}

.form-input,
.form-textarea,
.form-input-file {
    width: 100%;
    padding: var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    transition: border-color var(--transition-fast);
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-textarea {
    resize: vertical;
    font-family: inherit;
}

.form-input-file {
    padding: var(--spacing-2);
}

.character-count {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-top: var(--spacing-1);
}

.form-help {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: var(--spacing-2) 0 0 0;
}

.image-preview {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    padding: var(--spacing-4);
    background-color: var(--color-gray-50);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-3);
}

.preview-og-image {
    max-width: 300px;
    max-height: 157px;
    object-fit: cover;
    border-radius: var(--radius-sm);
}

.preview-info {
    flex: 1;
}

.preview-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin: 0 0 var(--spacing-1) 0;
}

.preview-dimensions {
    font-size: var(--font-size-xs);
    color: var(--color-gray-600);
    margin: 0;
}

.seo-preview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: var(--spacing-6);
}

.preview-heading {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin: 0 0 var(--spacing-3) 0;
}

.search-result {
    padding: var(--spacing-4);
    background-color: white;
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-md);
}

.search-title {
    font-size: var(--font-size-lg);
    color: #1a0dab;
    margin-bottom: var(--spacing-1);
    font-weight: var(--font-weight-normal);
}

.search-url {
    font-size: var(--font-size-sm);
    color: #006621;
    margin-bottom: var(--spacing-2);
}

.search-description {
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
    line-height: 1.5;
}

.social-card {
    border: 1px solid var(--color-gray-200);
    border-radius: var(--radius-md);
    overflow: hidden;
    background-color: white;
}

.social-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.social-image-placeholder {
    width: 100%;
    height: 200px;
    background-color: var(--color-gray-100);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-gray-500);
    font-size: var(--font-size-sm);
}

.social-content {
    padding: var(--spacing-4);
}

.social-title {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin-bottom: var(--spacing-2);
}

.social-description {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-bottom: var(--spacing-2);
    line-height: 1.4;
}

.social-url {
    font-size: var(--font-size-xs);
    color: var(--color-gray-500);
    text-transform: uppercase;
}

.form-actions {
    display: flex;
    gap: var(--spacing-3);
    padding-top: var(--spacing-4);
    border-top: 1px solid var(--color-gray-200);
}

.alert {
    padding: var(--spacing-4);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-6);
    font-size: var(--font-size-base);
}

.alert-success {
    background-color: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.alert-danger {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

@media (max-width: 768px) {
    .settings-nav {
        overflow-x: auto;
    }
    
    .admin-form {
        padding: var(--spacing-4);
    }
    
    .seo-preview {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<script>
// Update character counts and preview in real-time
document.addEventListener('DOMContentLoaded', function() {
    const metaTitle = document.getElementById('meta_title');
    const metaTitleCount = document.getElementById('meta_title_count');
    const metaDescription = document.getElementById('meta_description');
    const metaDescriptionCount = document.getElementById('meta_description_count');
    
    const previewTitle = document.getElementById('preview_title');
    const previewDescription = document.getElementById('preview_description');
    const previewSocialTitle = document.getElementById('preview_social_title');
    const previewSocialDescription = document.getElementById('preview_social_description');
    
    if (metaTitle && metaTitleCount) {
        metaTitle.addEventListener('input', function() {
            metaTitleCount.textContent = this.value.length;
            if (previewTitle) previewTitle.textContent = this.value || 'Your page title';
            if (previewSocialTitle) previewSocialTitle.textContent = this.value || 'Your page title';
        });
    }
    
    if (metaDescription && metaDescriptionCount) {
        metaDescription.addEventListener('input', function() {
            metaDescriptionCount.textContent = this.value.length;
            if (previewDescription) previewDescription.textContent = this.value || 'Your page description';
            if (previewSocialDescription) previewSocialDescription.textContent = this.value || 'Your page description';
        });
    }
});
</script>

<?php include_admin_footer(); ?>
