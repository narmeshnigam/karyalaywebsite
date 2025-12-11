<?php
/**
 * Customer Profile Page
 * View and edit profile information
 * 
 * Requirements: 5.5
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\User;
use Karyalay\Models\BillingAddress;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$userId = $user['id'];

// Initialize variables
$errors = [];
$success = false;

// Load billing address
$billingAddressModel = new BillingAddress();
$billingAddress = $billingAddressModel->findByCustomerId($userId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $formType = $_POST['form_type'] ?? 'profile';
        
        if ($formType === 'profile') {
            // Sanitize input
            $name = sanitizeString($_POST['name'] ?? '');
            $phone = sanitizeString($_POST['phone'] ?? '');
            $businessName = sanitizeString($_POST['business_name'] ?? '');
            
            // Validate required fields
            if (empty($name)) {
                $errors[] = 'Name is required';
            }
            
            // If no errors, update profile
            if (empty($errors)) {
                $userModel = new User();
                $updated = $userModel->update($userId, [
                    'name' => $name,
                    'phone' => $phone,
                    'business_name' => $businessName
                ]);
                
                if ($updated) {
                    // Refresh user data in session
                    $updatedUser = $userModel->findById($userId);
                    if ($updatedUser) {
                        $_SESSION['user'] = $updatedUser;
                        $_SESSION['user_name'] = $updatedUser['name'];
                        $user = $updatedUser;
                    }
                    
                    $success = true;
                    $_SESSION['flash_message'] = 'Profile updated successfully';
                    $_SESSION['flash_type'] = 'success';
                    
                    // Redirect to prevent form resubmission
                    header('Location: ' . get_app_base_url() . '/app/profile.php');
                    exit;
                } else {
                    $errors[] = 'Failed to update profile. Please try again.';
                }
            }
        } elseif ($formType === 'billing') {
            // Handle billing address update
            $billingData = [
                'full_name' => sanitizeString($_POST['billing_full_name'] ?? ''),
                'business_name' => sanitizeString($_POST['billing_business_name'] ?? ''),
                'business_tax_id' => sanitizeString($_POST['billing_business_tax_id'] ?? ''),
                'address_line1' => sanitizeString($_POST['billing_address_line1'] ?? ''),
                'address_line2' => sanitizeString($_POST['billing_address_line2'] ?? ''),
                'city' => sanitizeString($_POST['billing_city'] ?? ''),
                'state' => sanitizeString($_POST['billing_state'] ?? ''),
                'postal_code' => sanitizeString($_POST['billing_postal_code'] ?? ''),
                'country' => sanitizeString($_POST['billing_country'] ?? 'India'),
                'phone' => sanitizeString($_POST['billing_phone'] ?? '')
            ];
            
            // Validate required fields
            if (empty($billingData['full_name'])) {
                $errors[] = 'Full name is required';
            }
            if (empty($billingData['address_line1'])) {
                $errors[] = 'Address is required';
            }
            if (empty($billingData['city'])) {
                $errors[] = 'City is required';
            }
            if (empty($billingData['state'])) {
                $errors[] = 'State is required';
            }
            if (empty($billingData['postal_code'])) {
                $errors[] = 'Postal code is required';
            }
            if (empty($billingData['phone'])) {
                $errors[] = 'Phone number is required';
            }
            
            // If no errors, update billing address
            if (empty($errors)) {
                $updated = $billingAddressModel->createOrUpdate($userId, $billingData);
                
                if ($updated) {
                    // Reload billing address
                    $billingAddress = $billingAddressModel->findByCustomerId($userId);
                    
                    $_SESSION['flash_message'] = 'Billing address updated successfully';
                    $_SESSION['flash_type'] = 'success';
                    
                    // Redirect to prevent form resubmission
                    header('Location: ' . get_app_base_url() . '/app/profile.php');
                    exit;
                } else {
                    $errors[] = 'Failed to update billing address. Please try again.';
                }
            }
        }
    }
}

// Get user details for display
$userName = htmlspecialchars($user['name'] ?? '');
$userEmail = htmlspecialchars($user['email'] ?? '');
$userPhone = htmlspecialchars($user['phone'] ?? '');
$userBusinessName = htmlspecialchars($user['business_name'] ?? '');
$memberSince = isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A';

// Set page variables
$page_title = 'Profile';

// Include customer portal header
require_once __DIR__ . '/../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Profile Information</h2>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="info-box">
    <h3 class="info-box-title">Personal & Business Details</h3>
    <p class="info-box-description" style="margin-bottom: 1.5rem;">
        Update your personal and business information.
    </p>
    
    <form method="POST" action="<?php echo get_app_base_url(); ?>/app/profile.php" style="max-width: 600px;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="form_type" value="profile">
        
        <div class="form-group">
            <label for="name" class="form-label">Full Name <span style="color: #dc2626;">*</span></label>
            <input 
                type="text" 
                id="name" 
                name="name" 
                class="form-control" 
                value="<?php echo $userName; ?>" 
                required
                aria-required="true"
            >
        </div>
        
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control" 
                value="<?php echo $userEmail; ?>" 
                disabled
                aria-describedby="email-help"
            >
            <small id="email-help" class="form-help-text">
                Email cannot be changed. Contact support if you need to update your email.
            </small>
        </div>
        
        <div class="form-group">
            <label for="phone-input" class="form-label">Phone Number</label>
            <?php echo render_phone_input([
                'id' => 'phone',
                'name' => 'phone',
                'value' => $userPhone,
                'required' => false,
            ]); ?>
        </div>
        
        <div class="form-group">
            <label for="business_name" class="form-label">Business Name</label>
            <input 
                type="text" 
                id="business_name" 
                name="business_name" 
                class="form-control" 
                value="<?php echo $userBusinessName; ?>"
                placeholder="Your Company Name"
            >
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?php echo get_app_base_url(); ?>/app/dashboard.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<div class="info-box" style="margin-top: 2rem;">
    <h3 class="info-box-title">Billing Address</h3>
    <p class="info-box-description" style="margin-bottom: 1.5rem;">
        Manage your billing address for invoices and payments.
    </p>
    
    <form method="POST" action="<?php echo get_app_base_url(); ?>/app/profile.php" style="max-width: 600px;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="form_type" value="billing">
        
        <div class="form-group">
            <label for="billing_full_name" class="form-label">Full Name <span style="color: #dc2626;">*</span></label>
            <input 
                type="text" 
                id="billing_full_name" 
                name="billing_full_name" 
                class="form-control" 
                value="<?php echo htmlspecialchars($billingAddress['full_name'] ?? ''); ?>" 
                required
            >
        </div>
        
        <div class="form-group">
            <label for="billing_phone" class="form-label">Phone Number <span style="color: #dc2626;">*</span></label>
            <input 
                type="tel" 
                id="billing_phone" 
                name="billing_phone" 
                class="form-control" 
                value="<?php echo htmlspecialchars($billingAddress['phone'] ?? ''); ?>" 
                required
            >
        </div>
        
        <div class="form-group">
            <label for="billing_business_name" class="form-label">Business Name</label>
            <input 
                type="text" 
                id="billing_business_name" 
                name="billing_business_name" 
                class="form-control" 
                value="<?php echo htmlspecialchars($billingAddress['business_name'] ?? ''); ?>"
            >
        </div>
        
        <div class="form-group">
            <label for="billing_business_tax_id" class="form-label">Business Tax ID / GSTIN</label>
            <input 
                type="text" 
                id="billing_business_tax_id" 
                name="billing_business_tax_id" 
                class="form-control" 
                value="<?php echo htmlspecialchars($billingAddress['business_tax_id'] ?? ''); ?>"
                placeholder="e.g., GSTIN"
            >
        </div>
        
        <div class="form-group">
            <label for="billing_address_line1" class="form-label">Address Line 1 <span style="color: #dc2626;">*</span></label>
            <input 
                type="text" 
                id="billing_address_line1" 
                name="billing_address_line1" 
                class="form-control" 
                value="<?php echo htmlspecialchars($billingAddress['address_line1'] ?? ''); ?>" 
                required
            >
        </div>
        
        <div class="form-group">
            <label for="billing_address_line2" class="form-label">Address Line 2</label>
            <input 
                type="text" 
                id="billing_address_line2" 
                name="billing_address_line2" 
                class="form-control" 
                value="<?php echo htmlspecialchars($billingAddress['address_line2'] ?? ''); ?>"
            >
        </div>
        
        <div class="form-group">
            <label for="billing_city" class="form-label">City <span style="color: #dc2626;">*</span></label>
            <input 
                type="text" 
                id="billing_city" 
                name="billing_city" 
                class="form-control" 
                value="<?php echo htmlspecialchars($billingAddress['city'] ?? ''); ?>" 
                required
            >
        </div>
        
        <div class="form-group">
            <label for="billing_state" class="form-label">State <span style="color: #dc2626;">*</span></label>
            <input 
                type="text" 
                id="billing_state" 
                name="billing_state" 
                class="form-control" 
                value="<?php echo htmlspecialchars($billingAddress['state'] ?? ''); ?>" 
                required
            >
        </div>
        
        <div class="form-group">
            <label for="billing_postal_code" class="form-label">Postal Code <span style="color: #dc2626;">*</span></label>
            <input 
                type="text" 
                id="billing_postal_code" 
                name="billing_postal_code" 
                class="form-control" 
                value="<?php echo htmlspecialchars($billingAddress['postal_code'] ?? ''); ?>" 
                required
            >
        </div>
        
        <div class="form-group">
            <label for="billing_country" class="form-label">Country <span style="color: #dc2626;">*</span></label>
            <input 
                type="text" 
                id="billing_country" 
                name="billing_country" 
                class="form-control" 
                value="<?php echo htmlspecialchars($billingAddress['country'] ?? 'India'); ?>" 
                required
            >
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">Save Billing Address</button>
        </div>
    </form>
</div>

<div class="info-box" style="margin-top: 2rem;">
    <h3 class="info-box-title">Account Information</h3>
    <div class="info-box-content">
        <div class="info-box-row">
            <span class="info-box-label">Member Since</span>
            <span class="info-box-value"><?php echo $memberSince; ?></span>
        </div>
        <div class="info-box-row">
            <span class="info-box-label">Account Type</span>
            <span class="info-box-value"><?php echo ucfirst(strtolower($user['role'] ?? 'CUSTOMER')); ?></span>
        </div>
    </div>
</div>

<div class="quick-actions" style="margin-top: 2rem;">
    <a href="<?php echo get_app_base_url(); ?>/app/security.php" class="btn btn-outline">Security Settings</a>
</div>

<?php
// Include customer portal footer
require_once __DIR__ . '/../templates/customer-footer.php';
?>
