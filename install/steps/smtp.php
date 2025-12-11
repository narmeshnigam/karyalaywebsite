<?php
/**
 * Installation Wizard - SMTP Configuration Step
 * 
 * This step allows users to configure SMTP settings for email notifications.
 * 
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
 */

// This file is included by install/index.php
// Available variables: $installationService, $csrfService, $progress, $currentStep

$errors = [];
$formData = [];
$success = false;
$testResult = null;
$skipped = false;

// Check if admin account is created
if (!$progress['admin_created']) {
    // Redirect back to admin step
    header('Location: ?action=jump&step=3');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'smtp') {
    // Check if user wants to skip SMTP configuration
    if (isset($_POST['skip_smtp'])) {
        // Mark step as completed (but not configured)
        $progress['smtp_configured'] = false;
        if (!in_array(4, $progress['completed_steps'])) {
            $progress['completed_steps'][] = 4;
        }
        $progress['current_step'] = 5; // Move to brand step
        $installationService->saveProgress($progress);
        
        $skipped = true;
        
        // Redirect to next step after showing warning
        header('Refresh: 3; url=?action=next');
    } else {
        // Collect form data
        $formData = [
            'smtp_host' => trim($_POST['smtp_host'] ?? ''),
            'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
            'smtp_username' => trim($_POST['smtp_username'] ?? ''),
            'smtp_password' => $_POST['smtp_password'] ?? '',
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            'smtp_from_address' => trim($_POST['smtp_from_address'] ?? ''),
            'smtp_from_name' => trim($_POST['smtp_from_name'] ?? '')
        ];
        
        // Save form data to session immediately for recovery
        $installationService->saveStepData(4, $formData);
        
        // Server-side validation
        if (empty($formData['smtp_host'])) {
            $errors['smtp_host'] = 'SMTP host is required.';
        }
        
        if (empty($formData['smtp_port'])) {
            $errors['smtp_port'] = 'SMTP port is required.';
        } elseif (!filter_var($formData['smtp_port'], FILTER_VALIDATE_INT) || $formData['smtp_port'] < 1 || $formData['smtp_port'] > 65535) {
            $errors['smtp_port'] = 'Port must be a number between 1 and 65535.';
        }
        
        if (empty($formData['smtp_username'])) {
            $errors['smtp_username'] = 'SMTP username is required.';
        }
        
        if (empty($formData['smtp_password'])) {
            $errors['smtp_password'] = 'SMTP password is required.';
        }
        
        if (empty($formData['smtp_from_address'])) {
            $errors['smtp_from_address'] = 'From address is required.';
        } elseif (!filter_var($formData['smtp_from_address'], FILTER_VALIDATE_EMAIL)) {
            $errors['smtp_from_address'] = 'Please enter a valid email address.';
        }
        
        if (empty($formData['smtp_from_name'])) {
            $errors['smtp_from_name'] = 'From name is required.';
        }
        
        // If no validation errors, save SMTP settings
        if (empty($errors)) {
            // Save SMTP settings to database
            $saveSuccess = $installationService->saveSmtpSettings($formData);
            
            if ($saveSuccess) {
                // Mark step as completed
                $progress['smtp_configured'] = true;
                if (!in_array(4, $progress['completed_steps'])) {
                    $progress['completed_steps'][] = 4;
                }
                $progress['current_step'] = 5; // Move to brand step
                $installationService->saveProgress($progress);
                
                $success = true;
                
                // Redirect to next step after showing success message
                header('Refresh: 2; url=?action=next');
            } else {
                $errors['general'] = 'Failed to save SMTP settings. Please try again.';
            }
        }
    }
}

// Load saved data if available (for back navigation or page reload)
if (empty($formData)) {
    $savedData = $installationService->getStepData(4);
    if ($savedData !== null) {
        $formData = $savedData;
    }
}

// Set default values
$formData = array_merge([
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'smtp_from_address' => '',
    'smtp_from_name' => ''
], $formData);

?>

<div class="wizard-step">
    <h2>Step 4: SMTP Configuration</h2>
    <p class="step-description">
        Configure email settings to enable system notifications. You can test the connection before saving or skip this step.
    </p>
    
    <?php if (!empty($errors['general'])): ?>
        <?php echo displayError($errors['general'], 'SMTP Configuration Failed'); ?>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <?php echo displaySuccess('SMTP settings saved successfully! Redirecting to next step...', 'SMTP Configured'); ?>
    <?php endif; ?>
    
    <?php if ($skipped): ?>
        <?php echo displayWarning(
            'SMTP configuration skipped. Email features will not work until SMTP is configured. You can configure this later in the admin settings.',
            'SMTP Skipped'
        ); ?>
    <?php endif; ?>
    
    <form method="post" action="" class="wizard-form" id="smtp-form">
        <?php echo $csrfService->getTokenField(); ?>
        <input type="hidden" name="step" value="smtp">
        
        <div class="form-section">
            <h3>SMTP Server Settings</h3>
            
            <div class="form-group">
                <label class="form-label" for="smtp_host">
                    SMTP Host <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="smtp_host" 
                    id="smtp_host" 
                    class="form-input <?php echo isset($errors['smtp_host']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['smtp_host']); ?>"
                    placeholder="smtp.gmail.com"
                    required
                    <?php echo ($success || $skipped) ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['smtp_host'])): ?>
                    <span class="form-error visible"><?php echo $errors['smtp_host']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">Your email provider's SMTP server address</span>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="smtp_port">
                        Port <span class="required">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="smtp_port" 
                        id="smtp_port" 
                        class="form-input <?php echo isset($errors['smtp_port']) ? 'error' : ''; ?>"
                        value="<?php echo htmlspecialchars($formData['smtp_port']); ?>"
                        placeholder="587"
                        min="1"
                        max="65535"
                        required
                        <?php echo ($success || $skipped) ? 'disabled' : ''; ?>
                    >
                    <?php if (isset($errors['smtp_port'])): ?>
                        <span class="form-error visible"><?php echo $errors['smtp_port']; ?></span>
                    <?php else: ?>
                        <span class="form-error"></span>
                    <?php endif; ?>
                    <span class="form-help">Common: 587 (TLS), 465 (SSL), 25</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="smtp_encryption">
                        Encryption <span class="required">*</span>
                    </label>
                    <select 
                        name="smtp_encryption" 
                        id="smtp_encryption" 
                        class="form-select"
                        required
                        <?php echo ($success || $skipped) ? 'disabled' : ''; ?>
                    >
                        <option value="tls" <?php echo $formData['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>
                            TLS (Recommended)
                        </option>
                        <option value="ssl" <?php echo $formData['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>
                            SSL
                        </option>
                        <option value="none" <?php echo $formData['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>
                            None
                        </option>
                    </select>
                    <span class="form-error"></span>
                    <span class="form-help">Use TLS for port 587, SSL for port 465</span>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Authentication</h3>
            
            <div class="form-group">
                <label class="form-label" for="smtp_username">
                    Username <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="smtp_username" 
                    id="smtp_username" 
                    class="form-input <?php echo isset($errors['smtp_username']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['smtp_username']); ?>"
                    placeholder="your-email@example.com"
                    required
                    autocomplete="off"
                    <?php echo ($success || $skipped) ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['smtp_username'])): ?>
                    <span class="form-error visible"><?php echo $errors['smtp_username']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">Usually your email address</span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="smtp_password">
                    Password <span class="required">*</span>
                </label>
                <input 
                    type="password" 
                    name="smtp_password" 
                    id="smtp_password" 
                    class="form-input <?php echo isset($errors['smtp_password']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['smtp_password']); ?>"
                    placeholder="Enter SMTP password"
                    required
                    autocomplete="off"
                    <?php echo ($success || $skipped) ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['smtp_password'])): ?>
                    <span class="form-error visible"><?php echo $errors['smtp_password']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">Your email password or app-specific password</span>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Sender Information</h3>
            
            <div class="form-group">
                <label class="form-label" for="smtp_from_address">
                    From Address <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    name="smtp_from_address" 
                    id="smtp_from_address" 
                    class="form-input <?php echo isset($errors['smtp_from_address']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['smtp_from_address']); ?>"
                    placeholder="noreply@example.com"
                    required
                    <?php echo ($success || $skipped) ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['smtp_from_address'])): ?>
                    <span class="form-error visible"><?php echo $errors['smtp_from_address']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">Email address that will appear as sender</span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="smtp_from_name">
                    From Name <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="smtp_from_name" 
                    id="smtp_from_name" 
                    class="form-input <?php echo isset($errors['smtp_from_name']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['smtp_from_name']); ?>"
                    placeholder="SellerPortal"
                    required
                    <?php echo ($success || $skipped) ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['smtp_from_name'])): ?>
                    <span class="form-error visible"><?php echo $errors['smtp_from_name']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">Name that will appear as sender</span>
            </div>
        </div>
        
        <?php if (!$success && !$skipped): ?>
            <div class="form-actions">
                <button 
                    type="button" 
                    class="btn btn-secondary" 
                    id="test-smtp-btn"
                    data-test-action="smtp"
                >
                    Test SMTP Connection
                </button>
                
                <button type="submit" class="btn btn-primary">
                    Save & Continue →
                </button>
                
                <button 
                    type="submit" 
                    name="skip_smtp" 
                    value="1" 
                    class="btn btn-link"
                    onclick="return confirm('Are you sure you want to skip SMTP configuration? Email features will not work until this is configured.');"
                >
                    Skip This Step
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>
