<?php
/**
 * Installation Wizard - Admin Account Creation Step
 * 
 * This step allows users to create the root admin account.
 * 
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 10.4
 */

// This file is included by install/index.php
// Available variables: $installationService, $csrfService, $progress, $currentStep

$errors = [];
$formData = [];
$success = false;
$skipStep = false;

// Check if migrations are completed
if (!$progress['migrations_run']) {
    // Redirect back to migrations step
    header('Location: ?action=jump&step=2');
    exit;
}

// Check if in preserve mode and admin users already exist
$rerunMode = $installationService->getRerunMode();
if ($rerunMode === 'preserve') {
    $existingData = $installationService->detectExistingData();
    if ($existingData['details']['has_users']) {
        $skipStep = true;
        
        // Mark step as completed and move to next step
        if (!in_array(3, $progress['completed_steps'])) {
            $progress['completed_steps'][] = 3;
        }
        $progress['admin_created'] = true;
        $progress['current_step'] = 4;
        $installationService->saveProgress($progress);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'admin') {
    // Collect form data (excluding passwords for security)
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? ''
    ];
    
    // Save non-sensitive form data to session immediately for recovery
    $installationService->saveStepData(3, [
        'name' => $formData['name'],
        'email' => $formData['email']
    ]);
    
    // Server-side validation
    if (empty($formData['name'])) {
        $errors['name'] = 'Name is required.';
    }
    
    if (empty($formData['email'])) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    
    if (empty($formData['password'])) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($formData['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }
    
    if (empty($formData['password_confirm'])) {
        $errors['password_confirm'] = 'Please confirm your password.';
    } elseif ($formData['password'] !== $formData['password_confirm']) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }
    
    // If no validation errors, create admin user
    if (empty($errors)) {
        $result = $installationService->createAdminUser($formData);
        
        if ($result['success']) {
            // Save admin email to session for display on completion screen
            $_SESSION['admin_email'] = $formData['email'];
            
            // Mark step as completed
            $progress['admin_created'] = true;
            if (!in_array(3, $progress['completed_steps'])) {
                $progress['completed_steps'][] = 3;
            }
            $progress['current_step'] = 4; // Move to SMTP step
            $installationService->saveProgress($progress);
            
            $success = true;
            
            // Redirect to next step after showing success message
            header('Refresh: 2; url=?action=next');
        } else {
            $errors['general'] = $result['error'];
        }
    }
}

// Load saved data if available (for back navigation or page reload)
if (empty($formData)) {
    $savedData = $installationService->getStepData(3);
    if ($savedData !== null) {
        $formData = $savedData;
    }
}

// Set default values
$formData = array_merge([
    'name' => '',
    'email' => '',
    'password' => '',
    'password_confirm' => ''
], $formData);

?>

<div class="wizard-step">
    <h2>Step 3: Create Admin Account</h2>
    
    <?php if ($skipStep): ?>
        <div class="alert alert-info">
            <div class="alert-icon">ℹ</div>
            <div class="alert-content">
                <div class="alert-title">Existing Admin Users Detected</div>
                <div class="alert-message">
                    You chose to preserve existing data. Since admin users already exist in the database,
                    this step will be skipped. You can use your existing admin credentials to log in.
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a href="?action=next" class="btn btn-primary">Continue to Next Step →</a>
        </div>
    <?php else: ?>
    
    <p class="step-description">
        Create the root administrator account. You'll use these credentials to log in to the admin panel.
    </p>
    
    <?php if (!empty($errors['general'])): ?>
        <?php echo displayError($errors['general'], 'Admin Account Creation Failed'); ?>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <?php echo displaySuccess(
            'Admin account created successfully! Redirecting to next step...',
            'Account Created'
        ); ?>
        <div class="alert alert-info">
            <div class="alert-icon">ℹ</div>
            <div class="alert-content">
                <div class="alert-message">
                    <strong>Important:</strong> Please save your login credentials in a secure location.
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <form method="post" action="" class="wizard-form" id="admin-form">
        <?php echo $csrfService->getTokenField(); ?>
        <input type="hidden" name="step" value="admin">
        
        <div class="form-section">
            <h3>Administrator Details</h3>
            
            <div class="form-group">
                <label class="form-label" for="name">
                    Full Name <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    class="form-input <?php echo isset($errors['name']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['name']); ?>"
                    placeholder="John Doe"
                    required
                    autocomplete="name"
                    <?php echo $success ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['name'])): ?>
                    <span class="form-error visible"><?php echo $errors['name']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">
                    Email Address <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['email']); ?>"
                    placeholder="admin@example.com"
                    required
                    autocomplete="email"
                    <?php echo $success ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['email'])): ?>
                    <span class="form-error visible"><?php echo $errors['email']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">You'll use this email to log in to the admin panel</span>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Password</h3>
            <p class="form-help">
                Choose a strong password with at least 8 characters. Include uppercase, lowercase, numbers, and special characters for better security.
            </p>
            
            <div class="form-group">
                <label class="form-label" for="password">
                    Password <span class="required">*</span>
                </label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="form-input <?php echo isset($errors['password']) ? 'error' : ''; ?>"
                    value=""
                    placeholder="Enter a strong password"
                    required
                    autocomplete="new-password"
                    <?php echo $success ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['password'])): ?>
                    <span class="form-error visible"><?php echo $errors['password']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                
                <!-- Password Strength Indicator -->
                <div class="password-strength" style="margin-top: 12px;">
                    <div class="password-strength-bar"></div>
                    <div class="password-strength-text"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password_confirm">
                    Confirm Password <span class="required">*</span>
                </label>
                <input 
                    type="password" 
                    name="password_confirm" 
                    id="password_confirm" 
                    class="form-input <?php echo isset($errors['password_confirm']) ? 'error' : ''; ?>"
                    value=""
                    placeholder="Re-enter your password"
                    required
                    autocomplete="new-password"
                    <?php echo $success ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['password_confirm'])): ?>
                    <span class="form-error visible"><?php echo $errors['password_confirm']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!$success): ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Create Admin Account →
                </button>
            </div>
        <?php endif; ?>
    </form>
    
    <?php endif; // End of skipStep check ?>
</div>

<?php if (!$success): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirm');
    
    if (confirmInput) {
        confirmInput.addEventListener('input', function() {
            const errorElement = confirmInput.parentElement.querySelector('.form-error');
            
            if (confirmInput.value && passwordInput.value !== confirmInput.value) {
                confirmInput.classList.add('error');
                if (errorElement) {
                    errorElement.textContent = 'Passwords do not match.';
                    errorElement.classList.add('visible');
                }
            } else {
                confirmInput.classList.remove('error');
                if (errorElement) {
                    errorElement.classList.remove('visible');
                }
            }
        });
    }
});
</script>
<?php endif; ?>
