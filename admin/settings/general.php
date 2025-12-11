<?php
/**
 * Admin General Settings Page
 * Manage site configuration (contact email, phone, footer text)
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Models\Setting;
use Karyalay\Middleware\CsrfMiddleware;

// Start secure session
startSecureSession();

// Require admin authentication and settings.general permission
require_admin();
require_permission('settings.general');

// Initialize Setting model
$settingModel = new Setting();
$csrfMiddleware = new CsrfMiddleware();

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
            $site_name = trim($_POST['site_name'] ?? '');
            $contact_email = trim($_POST['contact_email'] ?? '');
            $contact_phone = trim($_POST['contact_phone'] ?? '');
            $contact_address = trim($_POST['contact_address'] ?? '');
            $notifications_email = trim($_POST['notifications_email'] ?? '');
            $business_hours_weekday = trim($_POST['business_hours_weekday'] ?? '');
            $business_hours_weekend = trim($_POST['business_hours_weekend'] ?? '');
            $footer_company_description = trim($_POST['footer_company_description'] ?? '');
            $footer_copyright_text = trim($_POST['footer_copyright_text'] ?? '');
            
            // Validate required fields
            if (empty($site_name)) {
                throw new Exception('Site name is required');
            }
            
            if (empty($contact_email)) {
                throw new Exception('Contact email is required');
            }
            
            if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format');
            }
            
            // Validate notifications email if provided
            if (!empty($notifications_email) && !filter_var($notifications_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid notifications email format');
            }
            
            // Save settings
            $settingModel->set('site_name', $site_name);
            $settingModel->set('contact_email', $contact_email);
            $settingModel->set('contact_phone', $contact_phone);
            $settingModel->set('contact_address', $contact_address);
            $settingModel->set('notifications_email', $notifications_email);
            $settingModel->set('business_hours_weekday', $business_hours_weekday);
            $settingModel->set('business_hours_weekend', $business_hours_weekend);
            $settingModel->set('footer_company_description', $footer_company_description);
            $settingModel->set('footer_copyright_text', $footer_copyright_text);
            
            $success_message = 'General settings saved successfully!';
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Fetch current settings
$settings = $settingModel->getMultiple([
    'site_name',
    'contact_email',
    'contact_phone',
    'contact_address',
    'notifications_email',
    'business_hours_weekday',
    'business_hours_weekend',
    'footer_company_description',
    'footer_copyright_text'
]);

// Set defaults if not found
$site_name = $settings['site_name'] ?? 'SellerPortal';
$contact_email = $settings['contact_email'] ?? '';
$contact_phone = $settings['contact_phone'] ?? '';
$contact_address = $settings['contact_address'] ?? '';
$notifications_email = $settings['notifications_email'] ?? '';
$business_hours_weekday = $settings['business_hours_weekday'] ?? 'Monday - Friday: 9:00 AM - 6:00 PM';
$business_hours_weekend = $settings['business_hours_weekend'] ?? 'Saturday - Sunday: Closed';
$footer_company_description = $settings['footer_company_description'] ?? 'Comprehensive business management system designed to streamline your operations and boost productivity.';
$footer_copyright_text = $settings['footer_copyright_text'] ?? '';

// Generate CSRF token
$csrf_token = getCsrfToken();

// Include admin header
include_admin_header('General Settings');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">General Settings</h1>
        <p class="admin-page-description">Manage site configuration and contact information</p>
    </div>
</div>

<?php $base_url = get_app_base_url(); ?>
<!-- Settings Navigation -->
<div class="settings-nav">
    <a href="<?php echo $base_url; ?>/admin/settings/general.php" class="settings-nav-item active">General</a>
    <a href="<?php echo $base_url; ?>/admin/settings/branding.php" class="settings-nav-item">Branding</a>
    <a href="<?php echo $base_url; ?>/admin/settings/seo.php" class="settings-nav-item">SEO</a>
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

<!-- General Settings Form -->
<div class="admin-card">
    <form method="POST" action="<?php echo $base_url; ?>/admin/settings/general.php" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-section">
            <h3 class="form-section-title">Site Information</h3>
            
            <div class="form-group">
                <label for="site_name" class="form-label">
                    Site Name <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="site_name" 
                    name="site_name" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($site_name); ?>"
                    required
                    maxlength="255"
                >
                <p class="form-help">The name of your site displayed in the header and page titles</p>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section-title">Contact Information</h3>
            
            <div class="form-group">
                <label for="contact_email" class="form-label">
                    Contact Email <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    id="contact_email" 
                    name="contact_email" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($contact_email); ?>"
                    required
                    maxlength="255"
                >
                <p class="form-help">Primary email address for customer inquiries and notifications</p>
            </div>
            
            <div class="form-group">
                <label for="contact_phone-input" class="form-label">
                    Contact Phone
                </label>
                <?php echo render_phone_input([
                    'id' => 'contact_phone',
                    'name' => 'contact_phone',
                    'value' => $contact_phone,
                    'required' => false,
                ]); ?>
                <p class="form-help">Phone number displayed on the contact page</p>
            </div>
            
            <div class="form-group">
                <label for="notifications_email" class="form-label">
                    Notifications Email
                </label>
                <input 
                    type="email" 
                    id="notifications_email" 
                    name="notifications_email" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($notifications_email); ?>"
                    maxlength="255"
                >
                <p class="form-help">Email address to receive lead notifications (defaults to contact email if not set)</p>
            </div>
            
            <div class="form-group">
                <label for="contact_address" class="form-label">
                    Contact Address
                </label>
                <textarea 
                    id="contact_address" 
                    name="contact_address" 
                    class="form-textarea" 
                    rows="3"
                    maxlength="500"
                ><?php echo htmlspecialchars($contact_address); ?></textarea>
                <p class="form-help">Physical address displayed on the contact page</p>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section-title">Business Hours</h3>
            
            <div class="form-group">
                <label for="business_hours_weekday" class="form-label">
                    Weekday Hours
                </label>
                <input 
                    type="text" 
                    id="business_hours_weekday" 
                    name="business_hours_weekday" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($business_hours_weekday); ?>"
                    maxlength="100"
                    placeholder="Monday - Friday: 9:00 AM - 6:00 PM"
                >
                <p class="form-help">Business hours for weekdays displayed on the contact page</p>
            </div>
            
            <div class="form-group">
                <label for="business_hours_weekend" class="form-label">
                    Weekend Hours
                </label>
                <input 
                    type="text" 
                    id="business_hours_weekend" 
                    name="business_hours_weekend" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($business_hours_weekend); ?>"
                    maxlength="100"
                    placeholder="Saturday - Sunday: Closed"
                >
                <p class="form-help">Business hours for weekends displayed on the contact page</p>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section-title">Footer Content</h3>
            
            <div class="form-group">
                <label for="footer_company_description" class="form-label">
                    Company Description
                </label>
                <textarea 
                    id="footer_company_description" 
                    name="footer_company_description" 
                    class="form-textarea" 
                    rows="3"
                    maxlength="500"
                    placeholder="Comprehensive business management system designed to streamline your operations and boost productivity."
                ><?php echo htmlspecialchars($footer_company_description); ?></textarea>
                <p class="form-help">Company description displayed in the footer next to your logo</p>
            </div>
            
            <div class="form-group">
                <label for="footer_copyright_text" class="form-label">
                    Copyright Text
                </label>
                <input 
                    type="text" 
                    id="footer_copyright_text" 
                    name="footer_copyright_text" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($footer_copyright_text); ?>"
                    maxlength="200"
                    placeholder="All rights reserved."
                >
                <p class="form-help">Additional copyright text (year and company name are added automatically)</p>
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
.form-textarea {
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

.form-help {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: var(--spacing-2) 0 0 0;
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
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<?php include_admin_footer(); ?>
