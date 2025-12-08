<?php
/**
 * Admin Branding Settings Page
 * Manage logo, favicon, and color scheme
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Models\Setting;
use Karyalay\Middleware\CsrfMiddleware;
use Karyalay\Services\MediaUploadService;

// Start secure session
startSecureSession();

// Require admin authentication
require_admin();

// Initialize services
$settingModel = new Setting();
$csrfMiddleware = new CsrfMiddleware();
$mediaUploadService = new MediaUploadService();

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrfMiddleware->validateToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        try {
            // Get form data
            $primary_color = trim($_POST['primary_color'] ?? '#3b82f6');
            $secondary_color = trim($_POST['secondary_color'] ?? '#10b981');
            
            // Validate color format (hex color)
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $primary_color)) {
                throw new Exception('Invalid primary color format. Use hex format (e.g., #3b82f6)');
            }
            
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $secondary_color)) {
                throw new Exception('Invalid secondary color format. Use hex format (e.g., #10b981)');
            }
            
            // Handle logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logo_result = $mediaUploadService->uploadFile(
                    $_FILES['logo'],
                    ['image/jpeg', 'image/png', 'image/svg+xml'],
                    5 * 1024 * 1024 // 5MB max
                );
                
                if ($logo_result['success']) {
                    $settingModel->set('logo_url', $logo_result['url']);
                } else {
                    throw new Exception('Logo upload failed: ' . $logo_result['error']);
                }
            }
            
            // Handle favicon upload
            if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
                $favicon_result = $mediaUploadService->uploadFile(
                    $_FILES['favicon'],
                    ['image/x-icon', 'image/png', 'image/svg+xml'],
                    1 * 1024 * 1024 // 1MB max
                );
                
                if ($favicon_result['success']) {
                    $settingModel->set('favicon_url', $favicon_result['url']);
                } else {
                    throw new Exception('Favicon upload failed: ' . $favicon_result['error']);
                }
            }
            
            // Save color settings
            $settingModel->set('primary_color', $primary_color);
            $settingModel->set('secondary_color', $secondary_color);
            
            $success_message = 'Branding settings saved successfully!';
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Fetch current settings
$settings = $settingModel->getMultiple([
    'logo_url',
    'favicon_url',
    'primary_color',
    'secondary_color'
]);

// Set defaults if not found
$logo_url = $settings['logo_url'] ?? '';
$favicon_url = $settings['favicon_url'] ?? '';
$primary_color = $settings['primary_color'] ?? '#3b82f6';
$secondary_color = $settings['secondary_color'] ?? '#10b981';

// Generate CSRF token
$csrf_token = getCsrfToken();

// Include admin header
include_admin_header('Branding Settings');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Branding Settings</h1>
        <p class="admin-page-description">Customize your site's visual identity</p>
    </div>
</div>

<!-- Settings Navigation -->
<div class="settings-nav">
    <a href="/karyalayportal/admin/settings/general.php" class="settings-nav-item">General</a>
    <a href="/karyalayportal/admin/settings/branding.php" class="settings-nav-item active">Branding</a>
    <a href="/karyalayportal/admin/settings/seo.php" class="settings-nav-item">SEO</a>
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

<!-- Branding Settings Form -->
<div class="admin-card">
    <form method="POST" action="/admin/settings/branding.php" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-section">
            <h3 class="form-section-title">Logo & Favicon</h3>
            
            <div class="form-group">
                <label for="logo" class="form-label">
                    Site Logo
                </label>
                
                <?php if ($logo_url): ?>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Current logo" class="preview-image">
                        <p class="preview-label">Current Logo</p>
                    </div>
                <?php endif; ?>
                
                <input 
                    type="file" 
                    id="logo" 
                    name="logo" 
                    class="form-input-file" 
                    accept="image/jpeg,image/png,image/svg+xml"
                >
                <p class="form-help">Upload a new logo (JPG, PNG, or SVG, max 5MB). Recommended size: 200x60px</p>
            </div>
            
            <div class="form-group">
                <label for="favicon" class="form-label">
                    Favicon
                </label>
                
                <?php if ($favicon_url): ?>
                    <div class="image-preview">
                        <img src="<?php echo htmlspecialchars($favicon_url); ?>" alt="Current favicon" class="preview-favicon">
                        <p class="preview-label">Current Favicon</p>
                    </div>
                <?php endif; ?>
                
                <input 
                    type="file" 
                    id="favicon" 
                    name="favicon" 
                    class="form-input-file" 
                    accept="image/x-icon,image/png,image/svg+xml"
                >
                <p class="form-help">Upload a new favicon (ICO, PNG, or SVG, max 1MB). Recommended size: 32x32px or 64x64px</p>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section-title">Color Scheme</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="primary_color" class="form-label">
                        Primary Color
                    </label>
                    <div class="color-input-group">
                        <input 
                            type="color" 
                            id="primary_color" 
                            name="primary_color" 
                            class="form-input-color" 
                            value="<?php echo htmlspecialchars($primary_color); ?>"
                        >
                        <input 
                            type="text" 
                            class="form-input form-input-hex" 
                            value="<?php echo htmlspecialchars($primary_color); ?>"
                            pattern="^#[0-9A-Fa-f]{6}$"
                            maxlength="7"
                            id="primary_color_hex"
                        >
                    </div>
                    <p class="form-help">Main brand color used for buttons, links, and accents</p>
                </div>
                
                <div class="form-group">
                    <label for="secondary_color" class="form-label">
                        Secondary Color
                    </label>
                    <div class="color-input-group">
                        <input 
                            type="color" 
                            id="secondary_color" 
                            name="secondary_color" 
                            class="form-input-color" 
                            value="<?php echo htmlspecialchars($secondary_color); ?>"
                        >
                        <input 
                            type="text" 
                            class="form-input form-input-hex" 
                            value="<?php echo htmlspecialchars($secondary_color); ?>"
                            pattern="^#[0-9A-Fa-f]{6}$"
                            maxlength="7"
                            id="secondary_color_hex"
                        >
                    </div>
                    <p class="form-help">Secondary brand color for complementary elements</p>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Save Settings
            </button>
            <a href="/karyalayportal/admin/dashboard.php" class="btn btn-secondary">
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
    margin: 0 0 var(--spacing-4) 0;
    padding-bottom: var(--spacing-3);
    border-bottom: 1px solid var(--color-gray-200);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-5);
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

.form-input,
.form-input-file {
    width: 100%;
    padding: var(--spacing-3);
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    font-size: var(--font-size-base);
    color: var(--color-gray-900);
    transition: border-color var(--transition-fast);
}

.form-input:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input-file {
    padding: var(--spacing-2);
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

.preview-image {
    max-width: 200px;
    max-height: 60px;
    object-fit: contain;
}

.preview-favicon {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.preview-label {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
}

.color-input-group {
    display: flex;
    gap: var(--spacing-3);
    align-items: center;
}

.form-input-color {
    width: 60px;
    height: 40px;
    border: 1px solid var(--color-gray-300);
    border-radius: var(--radius-md);
    cursor: pointer;
}

.form-input-hex {
    flex: 1;
    max-width: 150px;
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
    
    .form-row {
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
// Sync color picker with hex input
document.addEventListener('DOMContentLoaded', function() {
    const primaryColor = document.getElementById('primary_color');
    const primaryColorHex = document.getElementById('primary_color_hex');
    const secondaryColor = document.getElementById('secondary_color');
    const secondaryColorHex = document.getElementById('secondary_color_hex');
    
    if (primaryColor && primaryColorHex) {
        primaryColor.addEventListener('input', function() {
            primaryColorHex.value = this.value;
        });
        
        primaryColorHex.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                primaryColor.value = this.value;
            }
        });
    }
    
    if (secondaryColor && secondaryColorHex) {
        secondaryColor.addEventListener('input', function() {
            secondaryColorHex.value = this.value;
        });
        
        secondaryColorHex.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                secondaryColor.value = this.value;
            }
        });
    }
});
</script>

<?php include_admin_footer(); ?>
