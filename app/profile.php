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

use Karyalay\Models\User;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$userId = $user['id'];

// Initialize variables
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
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
                header('Location: /app/profile.php');
                exit;
            } else {
                $errors[] = 'Failed to update profile. Please try again.';
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
        <ul style="margin: 0; padding-left: 1.5rem;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="info-box">
    <h3 class="info-box-title">Personal & Business Details</h3>
    <p class="info-box-description" style="margin-bottom: 1.5rem; color: var(--color-gray-600);">
        Update your personal and business information.
    </p>
    
    <form method="POST" action="/app/profile.php" style="max-width: 600px;">
        <?php echo csrfField(); ?>
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="name" class="form-label">Full Name <span style="color: red;">*</span></label>
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
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
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
            <small id="email-help" style="display: block; margin-top: 0.25rem; color: var(--color-gray-600);">
                Email cannot be changed. Contact support if you need to update your email.
            </small>
        </div>
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="phone" class="form-label">Phone Number</label>
            <input 
                type="tel" 
                id="phone" 
                name="phone" 
                class="form-control" 
                value="<?php echo $userPhone; ?>"
                placeholder="+1 (555) 123-4567"
            >
        </div>
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
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
            <a href="/app/dashboard.php" class="btn btn-outline">Cancel</a>
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
    <a href="/app/security.php" class="btn btn-outline">Security Settings</a>
</div>

<?php
// Include customer portal footer
require_once __DIR__ . '/../templates/customer-footer.php';
?>
