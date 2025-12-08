<?php
/**
 * Installation Wizard - Brand Settings Step
 * 
 * This step allows users to configure brand settings and contact information.
 * 
 * Requirements: 5.1, 5.2, 5.3, 5.4
 */

// This file is included by install/index.php
// Available variables: $installationService, $csrfService, $progress, $currentStep

$errors = [];
$formData = [];
$success = false;
$logoPreview = null;

// Check if SMTP step is completed (or skipped)
if (!in_array(4, $progress['completed_steps'])) {
    // Redirect back to SMTP step
    header('Location: ?action=jump&step=4');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === 'brand') {
    // Collect form data
    $formData = [
        'company_name' => trim($_POST['company_name'] ?? ''),
        'company_tagline' => trim($_POST['company_tagline'] ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'contact_address' => trim($_POST['contact_address'] ?? '')
    ];
    
    // Save form data to session immediately for recovery
    $installationService->saveStepData(5, $formData);
    
    // Server-side validation
    if (empty($formData['company_name'])) {
        $errors['company_name'] = 'Company name is required.';
    }
    
    if (empty($formData['contact_email'])) {
        $errors['contact_email'] = 'Contact email is required.';
    } elseif (!filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['contact_email'] = 'Please enter a valid email address.';
    }
    
    // Process logo upload if provided
    $logoPath = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = $installationService->processLogoUpload($_FILES['logo']);
        
        if ($uploadResult['success']) {
            $logoPath = $uploadResult['path'];
            $formData['logo_path'] = $logoPath;
            // Update saved data with logo path
            $installationService->saveStepData(5, $formData);
        } else {
            $errors['logo'] = $uploadResult['error'];
        }
    }
    
    // If no validation errors, save brand settings
    if (empty($errors)) {
        // Save brand settings to database
        $saveSuccess = $installationService->saveBrandSettings($formData);
        
        if ($saveSuccess) {
            // Mark step as completed
            $progress['brand_configured'] = true;
            if (!in_array(5, $progress['completed_steps'])) {
                $progress['completed_steps'][] = 5;
            }
            $progress['current_step'] = 6; // Move to completion
            $installationService->saveProgress($progress);
            
            $success = true;
            
            // Redirect to completion after showing success message
            header('Refresh: 2; url=?action=complete');
        } else {
            $errors['general'] = 'Failed to save brand settings. Please try again.';
        }
    }
}

// Load saved data if available (for back navigation or page reload)
if (empty($formData)) {
    $savedData = $installationService->getStepData(5);
    if ($savedData !== null) {
        $formData = $savedData;
        if (!empty($formData['logo_path'])) {
            $logoPreview = $formData['logo_path'];
        }
    }
}

// Set default values
$formData = array_merge([
    'company_name' => '',
    'company_tagline' => '',
    'contact_email' => '',
    'contact_phone' => '',
    'contact_address' => ''
], $formData);

?>

<div class="wizard-step">
    <h2>Step 5: Brand Settings</h2>
    <p class="step-description">
        Customize your business information and branding. This information will appear throughout the website.
    </p>
    
    <?php if (!empty($errors['general'])): ?>
        <?php echo displayError($errors['general'], 'Brand Settings Failed'); ?>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <?php echo displaySuccess('Brand settings saved successfully! Completing installation...', 'Settings Saved'); ?>
    <?php endif; ?>
    
    <form method="post" action="" class="wizard-form" id="brand-form" enctype="multipart/form-data">
        <?php echo $csrfService->getTokenField(); ?>
        <input type="hidden" name="step" value="brand">
        
        <div class="form-section">
            <h3>Company Information</h3>
            
            <div class="form-group">
                <label class="form-label" for="company_name">
                    Company Name <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    name="company_name" 
                    id="company_name" 
                    class="form-input <?php echo isset($errors['company_name']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['company_name']); ?>"
                    placeholder="Your Company Name"
                    required
                    <?php echo $success ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['company_name'])): ?>
                    <span class="form-error visible"><?php echo $errors['company_name']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">This will appear in the website header and footer</span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="company_tagline">
                    Tagline
                </label>
                <input 
                    type="text" 
                    name="company_tagline" 
                    id="company_tagline" 
                    class="form-input <?php echo isset($errors['company_tagline']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['company_tagline']); ?>"
                    placeholder="Your company tagline or slogan"
                    <?php echo $success ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['company_tagline'])): ?>
                    <span class="form-error visible"><?php echo $errors['company_tagline']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">Optional: A short description of your business</span>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Contact Information</h3>
            
            <div class="form-group">
                <label class="form-label" for="contact_email">
                    Contact Email <span class="required">*</span>
                </label>
                <input 
                    type="email" 
                    name="contact_email" 
                    id="contact_email" 
                    class="form-input <?php echo isset($errors['contact_email']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['contact_email']); ?>"
                    placeholder="contact@example.com"
                    required
                    <?php echo $success ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['contact_email'])): ?>
                    <span class="form-error visible"><?php echo $errors['contact_email']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">Public email address for customer inquiries</span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="contact_phone">
                    Contact Phone
                </label>
                <input 
                    type="tel" 
                    name="contact_phone" 
                    id="contact_phone" 
                    class="form-input <?php echo isset($errors['contact_phone']) ? 'error' : ''; ?>"
                    value="<?php echo htmlspecialchars($formData['contact_phone']); ?>"
                    placeholder="+1 (555) 123-4567"
                    <?php echo $success ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['contact_phone'])): ?>
                    <span class="form-error visible"><?php echo $errors['contact_phone']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">Optional: Phone number for customer support</span>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="contact_address">
                    Business Address
                </label>
                <textarea 
                    name="contact_address" 
                    id="contact_address" 
                    class="form-input <?php echo isset($errors['contact_address']) ? 'error' : ''; ?>"
                    rows="3"
                    placeholder="123 Main Street&#10;City, State 12345&#10;Country"
                    <?php echo $success ? 'disabled' : ''; ?>
                ><?php echo htmlspecialchars($formData['contact_address']); ?></textarea>
                <?php if (isset($errors['contact_address'])): ?>
                    <span class="form-error visible"><?php echo $errors['contact_address']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">Optional: Physical business address</span>
            </div>
        </div>
        
        <div class="form-section">
            <h3>Logo</h3>
            
            <div class="form-group">
                <label class="form-label" for="logo">
                    Company Logo
                </label>
                
                <?php if ($logoPreview): ?>
                    <div class="logo-preview" id="logo-preview-container">
                        <img src="<?php echo htmlspecialchars($logoPreview); ?>" alt="Logo Preview" id="logo-preview-image">
                        <button type="button" class="btn btn-secondary btn-sm" id="remove-logo-btn">
                            Remove Logo
                        </button>
                    </div>
                <?php endif; ?>
                
                <input 
                    type="file" 
                    name="logo" 
                    id="logo" 
                    class="form-input <?php echo isset($errors['logo']) ? 'error' : ''; ?>"
                    accept="image/jpeg,image/png,image/gif,image/svg+xml"
                    <?php echo $success ? 'disabled' : ''; ?>
                >
                <?php if (isset($errors['logo'])): ?>
                    <span class="form-error visible"><?php echo $errors['logo']; ?></span>
                <?php else: ?>
                    <span class="form-error"></span>
                <?php endif; ?>
                <span class="form-help">Optional: Upload your company logo (JPG, PNG, GIF, or SVG, max 2MB)</span>
            </div>
        </div>
        
        <?php if (!$success): ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Complete Installation →
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if (!$success): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logo preview functionality
    const logoInput = document.getElementById('logo');
    const logoPreviewContainer = document.getElementById('logo-preview-container');
    const logoPreviewImage = document.getElementById('logo-preview-image');
    const removeLogoBtn = document.getElementById('remove-logo-btn');
    
    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file size (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size exceeds 2MB limit');
                    logoInput.value = '';
                    return;
                }
                
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
                if (!validTypes.includes(file.type)) {
                    alert('Invalid file type. Only JPG, PNG, GIF, and SVG are allowed');
                    logoInput.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (!logoPreviewContainer) {
                        // Create preview container if it doesn't exist
                        const container = document.createElement('div');
                        container.className = 'logo-preview';
                        container.id = 'logo-preview-container';
                        
                        const img = document.createElement('img');
                        img.id = 'logo-preview-image';
                        img.alt = 'Logo Preview';
                        img.src = e.target.result;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'btn btn-secondary btn-sm';
                        removeBtn.id = 'remove-logo-btn';
                        removeBtn.textContent = 'Remove Logo';
                        removeBtn.addEventListener('click', removeLogo);
                        
                        container.appendChild(img);
                        container.appendChild(removeBtn);
                        
                        logoInput.parentElement.insertBefore(container, logoInput);
                    } else {
                        logoPreviewImage.src = e.target.result;
                        logoPreviewContainer.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    function removeLogo() {
        if (logoInput) {
            logoInput.value = '';
        }
        if (logoPreviewContainer) {
            logoPreviewContainer.style.display = 'none';
        }
    }
    
    if (removeLogoBtn) {
        removeLogoBtn.addEventListener('click', removeLogo);
    }
    
    // Email validation
    const emailInput = document.getElementById('contact_email');
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const errorElement = emailInput.parentElement.querySelector('.form-error');
            
            if (emailInput.value && !isValidEmail(emailInput.value)) {
                emailInput.classList.add('error');
                if (errorElement) {
                    errorElement.textContent = 'Please enter a valid email address.';
                    errorElement.classList.add('visible');
                }
            } else {
                emailInput.classList.remove('error');
                if (errorElement) {
                    errorElement.classList.remove('visible');
                }
            }
        });
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
</script>
<?php endif; ?>
