<?php
/**
 * Admin Legal Identity Settings Page
 * Manage legal business information for invoices and legal documents
 */

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';

use Karyalay\Models\Setting;
use Karyalay\Middleware\CsrfMiddleware;

// Start secure session
startSecureSession();

// Require admin authentication and settings.general permission (ADMIN only)
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
            $legal_business_name = trim($_POST['legal_business_name'] ?? '');
            $legal_address_line1 = trim($_POST['legal_address_line1'] ?? '');
            $legal_address_line2 = trim($_POST['legal_address_line2'] ?? '');
            $legal_city = trim($_POST['legal_city'] ?? '');
            $legal_state = trim($_POST['legal_state'] ?? '');
            $legal_postal_code = trim($_POST['legal_postal_code'] ?? '');
            $legal_country = trim($_POST['legal_country'] ?? '');
            $billing_email = trim($_POST['billing_email'] ?? '');
            $billing_phone = trim($_POST['billing_phone'] ?? '');
            $business_tax_id = trim($_POST['business_tax_id'] ?? '');
            
            // Validate required fields
            if (empty($legal_business_name)) {
                throw new Exception('Legal business name is required');
            }
            
            // Validate billing email if provided
            if (!empty($billing_email) && !filter_var($billing_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid billing email format');
            }
            
            // Save settings
            $settingModel->setMultiple([
                'legal_business_name' => $legal_business_name,
                'legal_address_line1' => $legal_address_line1,
                'legal_address_line2' => $legal_address_line2,
                'legal_city' => $legal_city,
                'legal_state' => $legal_state,
                'legal_postal_code' => $legal_postal_code,
                'legal_country' => $legal_country,
                'billing_email' => $billing_email,
                'billing_phone' => $billing_phone,
                'business_tax_id' => $business_tax_id
            ]);
            
            $success_message = 'Legal identity settings saved successfully!';
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Fetch current settings
$settings = $settingModel->getMultiple([
    'legal_business_name',
    'legal_address_line1',
    'legal_address_line2',
    'legal_city',
    'legal_state',
    'legal_postal_code',
    'legal_country',
    'billing_email',
    'billing_phone',
    'business_tax_id'
]);

// Set defaults
$legal_business_name = $settings['legal_business_name'] ?? '';
$legal_address_line1 = $settings['legal_address_line1'] ?? '';
$legal_address_line2 = $settings['legal_address_line2'] ?? '';
$legal_city = $settings['legal_city'] ?? '';
$legal_state = $settings['legal_state'] ?? '';
$legal_postal_code = $settings['legal_postal_code'] ?? '';
$legal_country = $settings['legal_country'] ?? '';
$billing_email = $settings['billing_email'] ?? '';
$billing_phone = $settings['billing_phone'] ?? '';
$business_tax_id = $settings['business_tax_id'] ?? '';

// Generate CSRF token
$csrf_token = getCsrfToken();

// Include admin header
include_admin_header('Legal Identity Settings');
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Legal Identity Settings</h1>
        <p class="admin-page-description">Manage legal business information for invoices and legal documents</p>
    </div>
</div>

<?php $base_url = get_app_base_url(); ?>
<!-- Settings Navigation -->
<div class="settings-nav">
    <a href="<?php echo $base_url; ?>/admin/settings/general.php" class="settings-nav-item">General</a>
    <a href="<?php echo $base_url; ?>/admin/settings/branding.php" class="settings-nav-item">Branding</a>
    <a href="<?php echo $base_url; ?>/admin/settings/seo.php" class="settings-nav-item">SEO</a>
    <a href="<?php echo $base_url; ?>/admin/settings/legal-identity.php" class="settings-nav-item active">Legal Identity</a>
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

<!-- Legal Identity Settings Form -->
<div class="admin-card">
    <form method="POST" action="<?php echo $base_url; ?>/admin/settings/legal-identity.php" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        
        <div class="form-section">
            <h3 class="form-section-title">Business Information</h3>
            
            <div class="form-group">
                <label for="legal_business_name" class="form-label">
                    Legal Business Name <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    id="legal_business_name" 
                    name="legal_business_name" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($legal_business_name); ?>"
                    required
                    maxlength="255"
                    placeholder="Your Company Pvt. Ltd."
                >
                <p class="form-help">The official registered name of your business as it appears on legal documents</p>
            </div>
            
            <div class="form-group">
                <label for="business_tax_id" class="form-label">
                    Business Tax ID
                </label>
                <input 
                    type="text" 
                    id="business_tax_id" 
                    name="business_tax_id" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($business_tax_id); ?>"
                    maxlength="100"
                    placeholder="GST, VAT, PAN, or other tax identification number"
                >
                <p class="form-help">Your business tax identification number (GST, VAT, PAN, etc.) displayed on invoices</p>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section-title">Legal Address</h3>
            <p class="form-help" style="margin-top: -0.5rem; margin-bottom: 1rem;">This address will appear on invoices and legal documents</p>
            
            <div class="form-group">
                <label for="legal_address_line1" class="form-label">
                    Address Line 1
                </label>
                <input 
                    type="text" 
                    id="legal_address_line1" 
                    name="legal_address_line1" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($legal_address_line1); ?>"
                    maxlength="255"
                    placeholder="Street address, P.O. box"
                >
            </div>
            
            <div class="form-group">
                <label for="legal_address_line2" class="form-label">
                    Address Line 2
                </label>
                <input 
                    type="text" 
                    id="legal_address_line2" 
                    name="legal_address_line2" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($legal_address_line2); ?>"
                    maxlength="255"
                    placeholder="Apartment, suite, unit, building, floor, etc."
                >
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="legal_city" class="form-label">
                        City
                    </label>
                    <input 
                        type="text" 
                        id="legal_city" 
                        name="legal_city" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($legal_city); ?>"
                        maxlength="100"
                    >
                </div>
                
                <div class="form-group">
                    <label for="legal_state" class="form-label">
                        State / Province
                    </label>
                    <input 
                        type="text" 
                        id="legal_state" 
                        name="legal_state" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($legal_state); ?>"
                        maxlength="100"
                    >
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="legal_postal_code" class="form-label">
                        Postal / ZIP Code
                    </label>
                    <input 
                        type="text" 
                        id="legal_postal_code" 
                        name="legal_postal_code" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($legal_postal_code); ?>"
                        maxlength="20"
                    >
                </div>
                
                <div class="form-group">
                    <label for="legal_country" class="form-label">
                        Country
                    </label>
                    <input 
                        type="text" 
                        id="legal_country" 
                        name="legal_country" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($legal_country); ?>"
                        maxlength="100"
                    >
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3 class="form-section-title">Billing Contact</h3>
            <p class="form-help" style="margin-top: -0.5rem; margin-bottom: 1rem;">Contact information displayed on invoices for billing inquiries</p>
            
            <div class="form-group">
                <label for="billing_email" class="form-label">
                    Billing Email
                </label>
                <input 
                    type="email" 
                    id="billing_email" 
                    name="billing_email" 
                    class="form-input" 
                    value="<?php echo htmlspecialchars($billing_email); ?>"
                    maxlength="255"
                    placeholder="billing@yourcompany.com"
                >
                <p class="form-help">Email address for billing-related inquiries</p>
            </div>
            
            <div class="form-group">
                <label for="billing_phone-input" class="form-label">
                    Billing Phone
                </label>
                <?php echo render_phone_input([
                    'id' => 'billing_phone',
                    'name' => 'billing_phone',
                    'value' => $billing_phone,
                    'required' => false,
                ]); ?>
                <p class="form-help">Phone number for billing-related inquiries</p>
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

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-4);
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

<?php include_admin_footer(); ?>
