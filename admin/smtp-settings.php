3<?php
/**
 * SMTP Settings Management
 * Admin page to configure email server settings
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Setting;

// Start session and check admin authentication
startSecureSession();
require_admin();
require_permission('settings.smtp');

$settingModel = new Setting();
$success = null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        // Get form data
        $smtpHost = trim($_POST['smtp_host'] ?? '');
        $smtpPort = trim($_POST['smtp_port'] ?? '');
        $smtpUsername = trim($_POST['smtp_username'] ?? '');
        $smtpPassword = $_POST['smtp_password'] ?? '';
        $smtpEncryption = $_POST['smtp_encryption'] ?? 'tls';
        $smtpFromAddress = trim($_POST['smtp_from_address'] ?? '');
        $smtpFromName = trim($_POST['smtp_from_name'] ?? '');
        
        // Validate required fields
        $errors = [];
        
        if (empty($smtpHost)) {
            $errors[] = 'SMTP Host is required.';
        }
        
        if (empty($smtpPort)) {
            $errors[] = 'SMTP Port is required.';
        } elseif (!is_numeric($smtpPort) || (int)$smtpPort < 1 || (int)$smtpPort > 65535) {
            $errors[] = 'SMTP Port must be a number between 1 and 65535.';
        }
        
        if (empty($smtpUsername)) {
            $errors[] = 'SMTP Username is required.';
        }
        
        if (empty($smtpPassword)) {
            $errors[] = 'SMTP Password is required.';
        }
        
        if (!in_array($smtpEncryption, ['tls', 'ssl', 'none'])) {
            $errors[] = 'Invalid encryption type selected.';
        }
        
        if (empty($smtpFromAddress)) {
            $errors[] = 'From Address is required.';
        } elseif (!filter_var($smtpFromAddress, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'From Address must be a valid email address.';
        }
        
        if (empty($smtpFromName)) {
            $errors[] = 'From Name is required.';
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }
        
        // Prepare settings array
        $settings = [
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_username' => $smtpUsername,
            'smtp_password' => $smtpPassword,
            'smtp_encryption' => $smtpEncryption,
            'smtp_from_address' => $smtpFromAddress,
            'smtp_from_name' => $smtpFromName
        ];
        
        // Save settings
        if ($settingModel->setMultiple($settings)) {
            $success = 'SMTP settings saved successfully!';
            
            // Log the action
            $currentUser = getCurrentUser();
            if ($currentUser) {
                error_log('SMTP settings updated by admin: ' . $currentUser['email']);
            }
        } else {
            throw new Exception('Failed to save SMTP settings. Please try again.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('SMTP settings error: ' . $e->getMessage());
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Get current settings
$currentSettings = $settingModel->getMultiple([
    'smtp_host',
    'smtp_port',
    'smtp_username',
    'smtp_password',
    'smtp_encryption',
    'smtp_from_address',
    'smtp_from_name'
]);

$smtpHost = $currentSettings['smtp_host'] ?? '';
$smtpPort = $currentSettings['smtp_port'] ?? '';
$smtpUsername = $currentSettings['smtp_username'] ?? '';
$smtpPassword = $currentSettings['smtp_password'] ?? '';
$smtpEncryption = $currentSettings['smtp_encryption'] ?? 'tls';
$smtpFromAddress = $currentSettings['smtp_from_address'] ?? '';
$smtpFromName = $currentSettings['smtp_from_name'] ?? '';

// Page title
$page_title = 'SMTP Settings';

// Include admin header
include __DIR__ . '/../templates/admin-header.php';
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">SMTP Settings</h1>
        <p class="admin-page-description">Configure email server settings for sending emails</p>
    </div>
    <div class="admin-page-header-actions">
        <a href="<?php echo get_app_base_url(); ?>/admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>
</div>

<?php if ($success): ?>
            <div class="alert alert-success">
                <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="card-header">
                <h2 class="card-title">Email Server Configuration</h2>
            </div>
    <div class="card-body">
        <p class="card-description">Enter your SMTP server credentials to enable email sending</p>

        <form method="POST" action="" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <!-- Server Settings -->
                <div class="form-section">
                    <h3 class="form-section-title">Server Settings</h3>
                    
                    <div class="form-group">
                        <label for="smtp_host" class="form-label">
                            SMTP Host *
                        </label>
                        <input 
                            type="text" 
                            id="smtp_host" 
                            name="smtp_host" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($smtpHost); ?>"
                            placeholder="smtp.example.com"
                            required
                        >
                        <span class="form-help">
                            The hostname of your SMTP server (e.g., smtp.gmail.com, smtp.mailgun.org)
                        </span>
                    </div>

                    <div class="form-row">
                        <div class="form-group form-group-half">
                            <label for="smtp_port" class="form-label">
                                SMTP Port *
                            </label>
                            <input 
                                type="number" 
                                id="smtp_port" 
                                name="smtp_port" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($smtpPort); ?>"
                                placeholder="587"
                                min="1"
                                max="65535"
                                required
                            >
                            <span class="form-help">
                                Common ports: 587 (TLS), 465 (SSL), 25 (unencrypted)
                            </span>
                        </div>

                        <div class="form-group form-group-half">
                            <label for="smtp_encryption" class="form-label">
                                Encryption *
                            </label>
                            <select 
                                id="smtp_encryption" 
                                name="smtp_encryption" 
                                class="form-input"
                                required
                            >
                                <option value="tls" <?php echo $smtpEncryption === 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                                <option value="ssl" <?php echo $smtpEncryption === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo $smtpEncryption === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                            <span class="form-help">
                                TLS is recommended for secure email transmission
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Authentication -->
                <div class="form-section">
                    <h3 class="form-section-title">Authentication</h3>
                    
                    <div class="form-group">
                        <label for="smtp_username" class="form-label">
                            SMTP Username *
                        </label>
                        <input 
                            type="text" 
                            id="smtp_username" 
                            name="smtp_username" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($smtpUsername); ?>"
                            placeholder="your-email@example.com"
                            required
                        >
                        <span class="form-help">
                            Usually your email address or API key
                        </span>
                    </div>

                    <div class="form-group">
                        <label for="smtp_password" class="form-label">
                            SMTP Password *
                        </label>
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="smtp_password" 
                                name="smtp_password" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($smtpPassword); ?>"
                                placeholder="Enter your SMTP password"
                                required
                            >
                            <button type="button" class="btn btn-secondary" id="toggle-password-btn" aria-label="Toggle password visibility">
                                <svg class="btn-icon eye-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <span class="form-help">
                            Your SMTP password or app-specific password
                        </span>
                    </div>
                </div>

                <!-- Sender Information -->
                <div class="form-section">
                    <h3 class="form-section-title">Sender Information</h3>
                    
                    <div class="form-group">
                        <label for="smtp_from_address" class="form-label">
                            From Address *
                        </label>
                        <input 
                            type="email" 
                            id="smtp_from_address" 
                            name="smtp_from_address" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($smtpFromAddress); ?>"
                            placeholder="noreply@example.com"
                            required
                        >
                        <span class="form-help">
                            The email address that will appear as the sender
                        </span>
                    </div>

                    <div class="form-group">
                        <label for="smtp_from_name" class="form-label">
                            From Name *
                        </label>
                        <input 
                            type="text" 
                            id="smtp_from_name" 
                            name="smtp_from_name" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($smtpFromName); ?>"
                            placeholder="My Application"
                            required
                        >
                        <span class="form-help">
                            The display name that will appear as the sender
                        </span>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="alert alert-info">
                    <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <strong>Common SMTP Configurations:</strong>
                        <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                            <li><strong>Gmail:</strong> smtp.gmail.com, Port 587, TLS (requires App Password)</li>
                            <li><strong>Outlook/Office 365:</strong> smtp.office365.com, Port 587, TLS</li>
                            <li><strong>Mailgun:</strong> smtp.mailgun.org, Port 587, TLS</li>
                            <li><strong>SendGrid:</strong> smtp.sendgrid.net, Port 587, TLS</li>
                        </ul>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Settings
                    </button>
                    <button type="button" id="test-connection-btn" class="btn btn-secondary">
                        <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Test Connection
                    </button>

            </div>
        </form>
    </div>
</div>

<!-- Test Connection Result -->
<div id="test-result" class="alert" style="margin-top: 1.5rem; display: none;"></div>

<!-- Security Notice -->
<div class="alert alert-warning" style="margin-top: 1.5rem;">
    <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
    </svg>
    <div>
        <strong>Security Notice:</strong>
        <p style="margin: 0.25rem 0 0 0;">Your SMTP credentials are stored securely in the database. Never share your password with anyone. If using Gmail, consider using an App Password instead of your main account password.</p>
    </div>
</div>

<!-- Test Email Modal -->
<div id="test-email-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <svg class="modal-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Send Test Email
            </h2>
            <button type="button" class="modal-close" id="modal-close-btn" aria-label="Close modal">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="modal-body">
            <p class="modal-description">
                Connection successful! Enter an email address to send a test email and verify that email delivery is working properly.
            </p>
            
            <div class="form-group">
                <label for="test-email-address" class="form-label">
                    Email Address *
                </label>
                <input 
                    type="email" 
                    id="test-email-address" 
                    class="form-input" 
                    placeholder="your-email@example.com"
                    required
                >
                <span class="form-help">
                    Enter the email address where you want to receive the test email
                </span>
            </div>
            
            <div id="test-email-error" class="alert alert-danger" style="display: none; margin-top: 1rem;"></div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="modal-cancel-btn">
                Cancel
            </button>
            <button type="button" id="send-test-email-btn" class="btn btn-primary">
                <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Send Test Email
            </button>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
    } else {
        field.type = 'password';
    }
}

function testConnection() {
    const btn = document.getElementById('test-connection-btn');
    const resultDiv = document.getElementById('test-result');
    
    // Check if elements exist
    if (!btn || !resultDiv) {
        console.error('Required elements not found:', {
            btn: !!btn,
            resultDiv: !!resultDiv
        });
        return;
    }
    
    // Get form elements
    const csrfToken = document.querySelector('input[name="csrf_token"]');
    const smtpHost = document.getElementById('smtp_host');
    const smtpPort = document.getElementById('smtp_port');
    const smtpUsername = document.getElementById('smtp_username');
    const smtpPassword = document.getElementById('smtp_password');
    const smtpEncryption = document.getElementById('smtp_encryption');
    const smtpFromAddress = document.getElementById('smtp_from_address');
    const smtpFromName = document.getElementById('smtp_from_name');
    
    // Check if all form elements exist
    if (!csrfToken || !smtpHost || !smtpPort || !smtpUsername || !smtpPassword || !smtpEncryption || !smtpFromAddress || !smtpFromName) {
        console.error('Form elements not found:', {
            csrfToken: !!csrfToken,
            smtpHost: !!smtpHost,
            smtpPort: !!smtpPort,
            smtpUsername: !!smtpUsername,
            smtpPassword: !!smtpPassword,
            smtpEncryption: !!smtpEncryption,
            smtpFromAddress: !!smtpFromAddress,
            smtpFromName: !!smtpFromName
        });
        resultDiv.style.display = 'flex';
        resultDiv.className = 'alert alert-danger';
        resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>Form elements not found. Please refresh the page.</span>';
        return;
    }
    
    // Get form values
    const formData = new FormData();
    formData.append('csrf_token', csrfToken.value);
    formData.append('smtp_host', smtpHost.value);
    formData.append('smtp_port', smtpPort.value);
    formData.append('smtp_username', smtpUsername.value);
    formData.append('smtp_password', smtpPassword.value);
    formData.append('smtp_encryption', smtpEncryption.value);
    formData.append('smtp_from_address', smtpFromAddress.value);
    formData.append('smtp_from_name', smtpFromName.value);
    
    // Disable button and show loading state
    btn.disabled = true;
    btn.innerHTML = '<svg class="btn-icon spin" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Testing Connection...';
    
    // Hide previous results
    resultDiv.style.display = 'none';
    
    fetch('./api/test-smtp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        resultDiv.style.display = 'flex';
        if (data.success) {
            resultDiv.className = 'alert alert-success';
            resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' + data.message + '</span>';
            
            // If connection successful, show option to send test email
            if (data.can_send_test) {
                setTimeout(() => {
                    showTestEmailModal();
                }, 1000);
            }
        } else {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' + data.message + '</span>';
        }
    })
    .catch(error => {
        console.error('Test connection error:', error);
        resultDiv.style.display = 'flex';
        resultDiv.className = 'alert alert-danger';
        resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>Failed to test connection: ' + error.message + '. Please try again.</span>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg> Test Connection';
    });
}

function showTestEmailModal() {
    const modal = document.getElementById('test-email-modal');
    const emailInput = document.getElementById('test-email-address');
    const sendBtn = document.getElementById('send-test-email-btn');
    const modalCloseBtn = document.getElementById('modal-close-btn');
    const modalCancelBtn = document.getElementById('modal-cancel-btn');
    
    if (modal) {
        modal.style.display = 'flex';
    }
    
    if (emailInput) {
        emailInput.focus();
        
        // Attach Enter key handler
        emailInput.onkeypress = function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendTestEmail();
            }
        };
    }
    
    // Attach event listeners to modal buttons
    if (sendBtn) {
        sendBtn.onclick = sendTestEmail;
    }
    
    if (modalCloseBtn) {
        modalCloseBtn.onclick = closeTestEmailModal;
    }
    
    if (modalCancelBtn) {
        modalCancelBtn.onclick = closeTestEmailModal;
    }
}

function closeTestEmailModal() {
    const modal = document.getElementById('test-email-modal');
    const emailInput = document.getElementById('test-email-address');
    const errorDiv = document.getElementById('test-email-error');
    
    if (modal) {
        modal.style.display = 'none';
    }
    
    if (emailInput) {
        emailInput.value = '';
    }
    
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

function sendTestEmail() {
    // Wait a tiny bit for DOM to be ready
    setTimeout(function() {
        const emailInput = document.getElementById('test-email-address');
        let errorDiv = document.getElementById('test-email-error');
        const sendBtn = document.getElementById('send-test-email-btn');
        
        // Check if required modal elements exist
        if (!emailInput || !sendBtn) {
            console.error('Required modal elements not found:', {
                emailInput: !!emailInput,
                sendBtn: !!sendBtn
            });
            alert('Modal elements not ready. Please try again.');
            return;
        }
        
        // Create error div if it doesn't exist (this can happen if modal is initially hidden)
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'test-email-error';
            errorDiv.className = 'alert alert-danger';
            errorDiv.style.display = 'none';
            errorDiv.style.marginTop = '1rem';
            
            // Insert it after the form group
            const modalBody = document.querySelector('#test-email-modal .modal-body');
            if (modalBody) {
                modalBody.appendChild(errorDiv);
            } else {
                console.error('Modal body not found');
                alert('Modal structure error. Please refresh the page.');
                return;
            }
        }
        
        // Get result div (it's outside the modal, so it might not exist yet)
        const resultDiv = document.getElementById('test-result');
        
        sendTestEmailNow(emailInput, errorDiv, sendBtn, resultDiv);
    }, 100); // Increased timeout to 100ms
}

function sendTestEmailNow(emailInput, errorDiv, sendBtn, resultDiv) {
    
    const email = emailInput.value.trim();
    
    // Validate email
    if (!email) {
        errorDiv.textContent = 'Please enter an email address.';
        errorDiv.style.display = 'block';
        return;
    }
    
    if (!isValidEmail(email)) {
        errorDiv.textContent = 'Please enter a valid email address.';
        errorDiv.style.display = 'block';
        return;
    }
    
    errorDiv.style.display = 'none';
    
    // Get form values
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    formData.append('smtp_host', document.getElementById('smtp_host').value);
    formData.append('smtp_port', document.getElementById('smtp_port').value);
    formData.append('smtp_username', document.getElementById('smtp_username').value);
    formData.append('smtp_password', document.getElementById('smtp_password').value);
    formData.append('smtp_encryption', document.getElementById('smtp_encryption').value);
    formData.append('smtp_from_address', document.getElementById('smtp_from_address').value);
    formData.append('smtp_from_name', document.getElementById('smtp_from_name').value);
    formData.append('send_test_email', 'true');
    formData.append('test_email_address', email);
    
    // Disable button and show loading state
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<svg class="btn-icon spin" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Sending...';
    
    fetch('./api/test-smtp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Close modal
            closeTestEmailModal();
            
            // Show success message in result div if it exists
            if (resultDiv) {
                resultDiv.style.display = 'flex';
                resultDiv.className = 'alert alert-success';
                resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' + data.message + '</span>';
                
                // Scroll to result
                resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                // Fallback: show alert
                alert(data.message);
            }
        } else {
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Send test email error:', error);
        errorDiv.textContent = 'Failed to send test email: ' + error.message + '. Please try again.';
        errorDiv.style.display = 'block';
    })
    .finally(() => {
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg> Send Test Email';
    });
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('test-email-modal');
    if (event.target === modal) {
        closeTestEmailModal();
    }
}

// Setup event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Test connection button
    const testConnBtn = document.getElementById('test-connection-btn');
    if (testConnBtn) {
        testConnBtn.onclick = testConnection;
    }
    
    // Toggle password button
    const togglePwdBtn = document.getElementById('toggle-password-btn');
    if (togglePwdBtn) {
        togglePwdBtn.onclick = function() {
            togglePassword('smtp_password');
        };
    }
});
</script>

<style>
.admin-page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:var(--spacing-6);gap:var(--spacing-4)}
.admin-page-header-content{flex:1}
.admin-page-title{font-size:var(--font-size-2xl);font-weight:var(--font-weight-bold);color:var(--color-gray-900);margin:0 0 var(--spacing-2) 0}
.admin-page-description{font-size:var(--font-size-base);color:var(--color-gray-600);margin:0}
.admin-page-header-actions{display:flex;gap:var(--spacing-3)}
.card-header{padding:var(--spacing-5);border-bottom:1px solid var(--color-gray-200)}
.card-title{font-size:var(--font-size-lg);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin:0}
.card-body{padding:var(--spacing-5)}
.card-description{font-size:var(--font-size-sm);color:var(--color-gray-600);margin:0 0 var(--spacing-4) 0}
.form-row{display:flex;gap:1.5rem}
.form-group-half{flex:1}
.form-group{margin-bottom:var(--spacing-4)}
.form-label{display:block;font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);color:var(--color-gray-700);margin-bottom:var(--spacing-2)}
.form-input{width:100%;padding:var(--spacing-2) var(--spacing-3);border:1px solid var(--color-gray-300);border-radius:var(--radius-md);font-size:var(--font-size-base);color:var(--color-gray-900)}
.form-input:focus{outline:none;border-color:var(--color-primary);box-shadow:0 0 0 3px rgba(59,130,246,0.1)}
.form-help{font-size:var(--font-size-sm);color:var(--color-gray-600);margin:var(--spacing-1) 0 0 0}
.input-group{display:flex;gap:0.5rem}
.input-group .form-input{flex:1}
.input-group .btn{padding:0.5rem 1rem}
.btn-icon{width:18px;height:18px;flex-shrink:0}
.alert-icon{width:20px;height:20px;flex-shrink:0}
.form-section{margin-bottom:2rem;padding-bottom:2rem;border-bottom:1px solid var(--color-gray-200)}
.form-section:last-of-type{border-bottom:none}
.form-section-title{font-size:var(--font-size-lg);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin-bottom:1rem}
.form-actions{display:flex;gap:var(--spacing-3);margin-top:var(--spacing-6);padding-top:var(--spacing-4);border-top:1px solid var(--color-gray-200)}
.alert{padding:var(--spacing-4);border-radius:var(--radius-md);margin-bottom:var(--spacing-4);display:flex;align-items:flex-start;gap:var(--spacing-3)}
.alert-success{background-color:#f0fdf4;border:1px solid #86efac;color:#166534}
.alert-danger{background-color:#fef2f2;border:1px solid #fca5a5;color:#991b1b}
.alert-info{background-color:#eff6ff;border:1px solid #93c5fd;color:#1e40af}
.alert-warning{background-color:#fffbeb;border:1px solid #fcd34d;color:#92400e}
.alert ul{line-height:1.6;list-style-type:disc;margin:0.5rem 0 0 1.5rem}
.alert ul li{margin-bottom:0.25rem}
.spin{animation:spin 1s linear infinite}
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}

.modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);align-items:center;justify-content:center;animation:fadeIn 0.2s ease-in-out}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal-content{background-color:white;border-radius:var(--radius-xl);box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04);max-width:500px;width:90%;max-height:90vh;overflow-y:auto;animation:slideUp 0.3s ease-out}
@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:var(--spacing-5);border-bottom:1px solid var(--color-gray-200)}
.modal-title{display:flex;align-items:center;gap:var(--spacing-3);font-size:var(--font-size-xl);font-weight:var(--font-weight-semibold);color:var(--color-gray-900);margin:0}
.modal-icon{width:24px;height:24px;color:var(--color-primary)}
.modal-close{background:none;border:none;cursor:pointer;padding:var(--spacing-2);color:var(--color-gray-500);transition:all 0.2s;border-radius:var(--radius-md)}
.modal-close:hover{color:var(--color-gray-700);background-color:var(--color-gray-100)}
.modal-body{padding:var(--spacing-5)}
.modal-description{color:var(--color-gray-600);margin:0 0 var(--spacing-4) 0;line-height:1.6}
.modal-footer{display:flex;align-items:center;justify-content:flex-end;gap:var(--spacing-3);padding:var(--spacing-5);border-top:1px solid var(--color-gray-200);background-color:var(--color-gray-50);border-radius:0 0 var(--radius-xl) var(--radius-xl)}
@media(max-width:768px){.admin-page-header{flex-direction:column}.form-row{flex-direction:column;gap:0}.modal-content{width:95%;margin:1rem}.modal-footer{flex-direction:column-reverse}.modal-footer .btn{width:100%}}
</style>

<?php
// Include admin footer
include __DIR__ . '/../templates/admin-footer.php';
?>
