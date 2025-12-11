<?php
/**
 * Customer Security Settings Page
 * Change password and manage security settings
 * 
 * Requirements: 2.5
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

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
        // Get form data
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate required fields
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        }
        
        if (empty($confirmPassword)) {
            $errors[] = 'Password confirmation is required';
        }
        
        // Validate password match
        if (!empty($newPassword) && !empty($confirmPassword) && $newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match';
        }
        
        // Validate password strength
        if (!empty($newPassword) && strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long';
        }
        
        // If no errors, attempt to change password
        if (empty($errors)) {
            $result = changePassword($currentPassword, $newPassword);
            
            if ($result['success']) {
                $success = true;
                $_SESSION['flash_message'] = 'Password changed successfully';
                $_SESSION['flash_type'] = 'success';
                
                // Redirect to prevent form resubmission
                header('Location: ' . get_app_base_url() . '/app/security.php');
                exit;
            } else {
                $errors[] = $result['error'] ?? 'Failed to change password. Please try again.';
            }
        }
    }
}

// Get user details for display
$userName = htmlspecialchars($user['name'] ?? 'User');
$userEmail = htmlspecialchars($user['email'] ?? '');
$memberSince = isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A';
$lastLogin = date('M d, Y g:i A');

// Set page variables
$page_title = 'Security Settings';

// Include customer portal header
require_once __DIR__ . '/../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Security Settings</h2>
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
    <h3 class="info-box-title">Change Password</h3>
    <p class="info-box-description" style="margin-bottom: 1.5rem;">
        Update your password to keep your account secure. Your password must be at least 8 characters long.
    </p>
    
    <form method="POST" action="<?php echo get_app_base_url(); ?>/app/security.php" style="max-width: 500px;">
        <?php echo csrfField(); ?>
        
        <div class="form-group">
            <label for="current_password" class="form-label">Current Password <span style="color: #dc2626;">*</span></label>
            <input 
                type="password" 
                id="current_password" 
                name="current_password" 
                class="form-control" 
                required
                aria-required="true"
                autocomplete="current-password"
            >
        </div>
        
        <div class="form-group">
            <label for="new_password" class="form-label">New Password <span style="color: #dc2626;">*</span></label>
            <input 
                type="password" 
                id="new_password" 
                name="new_password" 
                class="form-control" 
                required
                aria-required="true"
                aria-describedby="password-help"
                autocomplete="new-password"
                minlength="8"
            >
            <small id="password-help" class="form-help-text">
                Must be at least 8 characters long
            </small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password" class="form-label">Confirm New Password <span style="color: #dc2626;">*</span></label>
            <input 
                type="password" 
                id="confirm_password" 
                name="confirm_password" 
                class="form-control" 
                required
                aria-required="true"
                autocomplete="new-password"
                minlength="8"
            >
        </div>
        
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn btn-primary">Change Password</button>
            <a href="<?php echo get_app_base_url(); ?>/app/dashboard.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<div class="info-box" style="margin-top: 2rem;">
    <h3 class="info-box-title">Account Activity</h3>
    <div class="info-box-content">
        <div class="info-box-row">
            <span class="info-box-label">Account Email</span>
            <span class="info-box-value"><?php echo $userEmail; ?></span>
        </div>
        <div class="info-box-row">
            <span class="info-box-label">Last Login</span>
            <span class="info-box-value"><?php echo $lastLogin; ?></span>
        </div>
        <div class="info-box-row">
            <span class="info-box-label">Member Since</span>
            <span class="info-box-value"><?php echo $memberSince; ?></span>
        </div>
    </div>
</div>

<div class="info-box" style="margin-top: 2rem;">
    <h3 class="info-box-title">Security Tips</h3>
    <div style="padding: 1rem 0;">
        <ul style="margin: 0; padding-left: 1.5rem; color: var(--color-gray-700);">
            <li style="margin-bottom: 0.5rem;">Use a strong, unique password that you don't use elsewhere</li>
            <li style="margin-bottom: 0.5rem;">Change your password regularly (every 3-6 months)</li>
            <li style="margin-bottom: 0.5rem;">Never share your password with anyone</li>
            <li style="margin-bottom: 0.5rem;">Log out when using shared or public computers</li>
        </ul>
    </div>
</div>

<div class="quick-actions" style="margin-top: 2rem;">
    <a href="<?php echo get_app_base_url(); ?>/app/profile.php" class="btn btn-outline">Back to Profile</a>
</div>

<?php
// Include customer portal footer
require_once __DIR__ . '/../templates/customer-footer.php';
?>
