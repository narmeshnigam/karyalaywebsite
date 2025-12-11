<?php
/**
 * Payment Settings Management
 * Admin page to configure payment gateway credentials
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/admin_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Setting;

// Start session and check admin authentication
startSecureSession();
require_admin();
require_permission('settings.payment');

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
        $razorpayKeyId = trim($_POST['razorpay_key_id'] ?? '');
        $razorpayKeySecret = trim($_POST['razorpay_key_secret'] ?? '');
        $razorpayWebhookSecret = trim($_POST['razorpay_webhook_secret'] ?? '');
        $paymentMode = $_POST['payment_mode'] ?? 'test';
        
        // Validate required fields
        if (empty($razorpayKeyId)) {
            throw new Exception('Razorpay Key ID is required.');
        }
        
        if (empty($razorpayKeySecret)) {
            throw new Exception('Razorpay Key Secret is required.');
        }
        
        // Prepare settings array
        $settings = [
            'razorpay_key_id' => $razorpayKeyId,
            'razorpay_key_secret' => $razorpayKeySecret,
            'razorpay_webhook_secret' => $razorpayWebhookSecret,
            'payment_mode' => $paymentMode,
            'payment_gateway' => 'razorpay'
        ];
        
        // Save settings
        if ($settingModel->setMultiple($settings)) {
            $success = 'Payment settings saved successfully!';
            
            // Log the action
            $currentUser = getCurrentUser();
            if ($currentUser) {
                error_log('Payment settings updated by admin: ' . $currentUser['email']);
            }
        } else {
            throw new Exception('Failed to save payment settings. Please try again.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log('Payment settings error: ' . $e->getMessage());
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Get current settings
$currentSettings = $settingModel->getMultiple([
    'razorpay_key_id',
    'razorpay_key_secret',
    'razorpay_webhook_secret',
    'payment_mode',
    'payment_gateway'
]);

$razorpayKeyId = $currentSettings['razorpay_key_id'] ?? '';
$razorpayKeySecret = $currentSettings['razorpay_key_secret'] ?? '';
$razorpayWebhookSecret = $currentSettings['razorpay_webhook_secret'] ?? '';
$paymentMode = $currentSettings['payment_mode'] ?? 'test';

// Page title
$page_title = 'Payment Settings';

// Include admin header
include __DIR__ . '/../templates/admin-header.php';
?>

<div class="admin-page-header">
    <div class="admin-page-header-content">
        <h1 class="admin-page-title">Payment Settings</h1>
        <p class="admin-page-description">Configure payment gateway credentials and settings</p>
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
                <h2 class="card-title">Razorpay Configuration</h2>
            </div>
            <div class="card-body">
                <p class="card-description">Enter your Razorpay API credentials to enable payment processing</p>

            <form method="POST" action="" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <!-- Payment Mode -->
                <div class="form-section">
                    <h3 class="form-section-title">Payment Mode</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Select Mode</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input 
                                    type="radio" 
                                    name="payment_mode" 
                                    value="test" 
                                    <?php echo $paymentMode === 'test' ? 'checked' : ''; ?>
                                    class="radio-input"
                                >
                                <span class="radio-text">
                                    <strong>Test Mode</strong>
                                    <small>Use test API keys for development and testing</small>
                                </span>
                            </label>
                            
                            <label class="radio-label">
                                <input 
                                    type="radio" 
                                    name="payment_mode" 
                                    value="live" 
                                    <?php echo $paymentMode === 'live' ? 'checked' : ''; ?>
                                    class="radio-input"
                                >
                                <span class="radio-text">
                                    <strong>Live Mode</strong>
                                    <small>Use live API keys for production payments</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- API Credentials -->
                <div class="form-section">
                    <h3 class="form-section-title">API Credentials</h3>
                    
                    <div class="form-group">
                        <label for="razorpay_key_id" class="form-label">
                            Razorpay Key ID *
                        </label>
                        <input 
                            type="text" 
                            id="razorpay_key_id" 
                            name="razorpay_key_id" 
                            class="form-input" 
                            value="<?php echo htmlspecialchars($razorpayKeyId); ?>"
                            placeholder="rzp_test_xxxxxxxxxx or rzp_live_xxxxxxxxxx"
                            required
                        >
                        <span class="form-help">
                            Your Razorpay Key ID (starts with rzp_test_ or rzp_live_)
                        </span>
                    </div>

                    <div class="form-group">
                        <label for="razorpay_key_secret" class="form-label">
                            Razorpay Key Secret *
                        </label>
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="razorpay_key_secret" 
                                name="razorpay_key_secret" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($razorpayKeySecret); ?>"
                                placeholder="Enter your Razorpay Key Secret"
                                required
                            >
                            <button type="button" class="btn btn-secondary" onclick="togglePassword('razorpay_key_secret')">
                                <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <span class="form-help">
                            Your Razorpay Key Secret (keep this confidential)
                        </span>
                    </div>

                    <div class="form-group">
                        <label for="razorpay_webhook_secret" class="form-label">
                            Webhook Secret (Optional)
                        </label>
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="razorpay_webhook_secret" 
                                name="razorpay_webhook_secret" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($razorpayWebhookSecret); ?>"
                                placeholder="Enter your Razorpay Webhook Secret"
                            >
                            <button type="button" class="btn btn-secondary" onclick="togglePassword('razorpay_webhook_secret')">
                                <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <span class="form-help">
                            Webhook secret for verifying payment notifications
                        </span>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="alert alert-info">
                    <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <strong>How to get your Razorpay credentials:</strong>
                        <ol style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                            <li>Log in to your <a href="https://dashboard.razorpay.com/" target="_blank" rel="noopener" style="color: inherit; text-decoration: underline;">Razorpay Dashboard</a></li>
                            <li>Go to Settings → API Keys</li>
                            <li>Generate or copy your Key ID and Key Secret</li>
                            <li>For webhooks, go to Settings → Webhooks and create a new webhook</li>
                        </ol>
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
        <p style="margin: 0.25rem 0 0 0;">Your API credentials are stored securely in the database. Never share your Key Secret or Webhook Secret with anyone. Always use test mode credentials during development.</p>
    </div>
</div>

<!-- Test Payment Modal -->
<div id="test-payment-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <svg class="modal-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Test Payment - ₹1.00
            </h2>
            <button type="button" class="modal-close" id="modal-close-btn" aria-label="Close modal">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="modal-body">
            <p class="modal-description">
                Connection verified! Click below to process a test payment of <strong>₹1.00 INR</strong> to verify the complete payment flow.
            </p>
            
            <div class="test-payment-info">
                <div class="info-item">
                    <span class="info-label">Amount:</span>
                    <span class="info-value">₹1.00 INR</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Mode:</span>
                    <span class="info-value" id="payment-mode-display">Test</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Purpose:</span>
                    <span class="info-value">Gateway Verification</span>
                </div>
            </div>
            
            <div class="alert alert-info" style="margin-top: 1rem;">
                <svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <strong>Note:</strong> The test payment will be automatically refunded after successful verification.
                </div>
            </div>
            
            <div id="test-payment-error" class="alert alert-danger" style="display: none; margin-top: 1rem;"></div>
            <div id="test-payment-success" class="alert alert-success" style="display: none; margin-top: 1rem;"></div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="modal-cancel-btn">
                Cancel
            </button>
            <button type="button" id="process-test-payment-btn" class="btn btn-primary">
                <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Process Test Payment
            </button>
        </div>
    </div>
</div>

<!-- Razorpay Checkout Script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
// Store credentials for test payment
let testPaymentCredentials = {
    keyId: '',
    keySecret: '',
    csrfToken: ''
};

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
    
    if (!btn || !resultDiv) {
        console.error('Required elements not found');
        return;
    }
    
    // Get form values
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    const keyId = document.getElementById('razorpay_key_id').value.trim();
    const keySecret = document.getElementById('razorpay_key_secret').value.trim();
    
    // Validate
    if (!keyId || !keySecret) {
        resultDiv.style.display = 'flex';
        resultDiv.className = 'alert alert-danger';
        resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>Please enter both Key ID and Key Secret before testing.</span>';
        return;
    }
    
    // Store for later use
    testPaymentCredentials = { keyId, keySecret, csrfToken };
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<svg class="btn-icon spin" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Verifying...';
    resultDiv.style.display = 'none';
    
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('razorpay_key_id', keyId);
    formData.append('razorpay_key_secret', keySecret);
    formData.append('action', 'verify');
    
    fetch('./api/test-payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.style.display = 'flex';
        if (data.success) {
            resultDiv.className = 'alert alert-success';
            resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' + data.message + '</span>';
            
            // Show test payment modal after a short delay
            if (data.can_test_payment) {
                document.getElementById('payment-mode-display').textContent = data.mode === 'test' ? 'Test Mode' : 'Live Mode';
                setTimeout(() => showTestPaymentModal(), 1000);
            }
        } else {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' + data.message + '</span>';
        }
    })
    .catch(error => {
        resultDiv.style.display = 'flex';
        resultDiv.className = 'alert alert-danger';
        resultDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>Connection test failed: ' + error.message + '</span>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg> Test Connection';
    });
}

function showTestPaymentModal() {
    const modal = document.getElementById('test-payment-modal');
    if (modal) {
        modal.style.display = 'flex';
        // Reset states
        const errorDiv = document.getElementById('test-payment-error');
        const successDiv = document.getElementById('test-payment-success');
        const processBtn = document.getElementById('process-test-payment-btn');
        
        if (errorDiv) errorDiv.style.display = 'none';
        if (successDiv) successDiv.style.display = 'none';
        if (processBtn) {
            processBtn.disabled = false;
            processBtn.className = 'btn btn-primary';
            processBtn.innerHTML = '<svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg> Process Test Payment';
        }
    }
}

function closeTestPaymentModal() {
    const modal = document.getElementById('test-payment-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function processTestPayment() {
    const btn = document.getElementById('process-test-payment-btn');
    const errorDiv = document.getElementById('test-payment-error');
    const successDiv = document.getElementById('test-payment-success');
    
    // Validate elements exist
    if (!btn) {
        console.error('Process button not found');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<svg class="btn-icon spin" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Creating Order...';
    if (errorDiv) errorDiv.style.display = 'none';
    if (successDiv) successDiv.style.display = 'none';
    
    // Step 1: Create test order
    const formData = new FormData();
    formData.append('csrf_token', testPaymentCredentials.csrfToken);
    formData.append('razorpay_key_id', testPaymentCredentials.keyId);
    formData.append('razorpay_key_secret', testPaymentCredentials.keySecret);
    formData.append('action', 'create_test_order');
    
    fetch('./api/test-payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message);
        }
        
        // Step 2: Open Razorpay checkout
        const options = {
            key: data.key_id,
            amount: data.amount,
            currency: data.currency,
            name: 'Payment Gateway Test',
            description: 'Test Payment - ₹1.00',
            order_id: data.order_id,
            handler: function(response) {
                verifyTestPayment(response);
            },
            prefill: {
                name: 'Admin Test',
                email: 'admin@test.com'
            },
            theme: {
                color: '#667eea'
            },
            modal: {
                ondismiss: function() {
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg> Process Test Payment';
                }
            }
        };
        
        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function(response) {
            const errDiv = document.getElementById('test-payment-error');
            if (errDiv) {
                errDiv.textContent = 'Payment failed: ' + response.error.description;
                errDiv.style.display = 'block';
            }
            btn.disabled = false;
            btn.innerHTML = '<svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg> Process Test Payment';
        });
        rzp.open();
    })
    .catch(error => {
        const errDiv = document.getElementById('test-payment-error');
        if (errDiv) {
            errDiv.textContent = error.message;
            errDiv.style.display = 'block';
        }
        btn.disabled = false;
        btn.innerHTML = '<svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg> Process Test Payment';
    });
}

function verifyTestPayment(response) {
    const btn = document.getElementById('process-test-payment-btn');
    const errorDiv = document.getElementById('test-payment-error');
    const successDiv = document.getElementById('test-payment-success');
    
    if (btn) {
        btn.innerHTML = '<svg class="btn-icon spin" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Verifying Payment...';
    }
    
    const formData = new FormData();
    formData.append('csrf_token', testPaymentCredentials.csrfToken);
    formData.append('razorpay_key_id', testPaymentCredentials.keyId);
    formData.append('razorpay_key_secret', testPaymentCredentials.keySecret);
    formData.append('action', 'verify_payment');
    formData.append('razorpay_order_id', response.razorpay_order_id);
    formData.append('razorpay_payment_id', response.razorpay_payment_id);
    formData.append('razorpay_signature', response.razorpay_signature);
    
    fetch('./api/test-payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let message = data.message;
            if (data.refund && data.refund.refund_id) {
                message += ' Amount has been automatically refunded.';
            }
            if (successDiv) {
                successDiv.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' + message + '</span>';
                successDiv.style.display = 'flex';
            }
            if (btn) {
                btn.innerHTML = '<svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Test Complete!';
                btn.className = 'btn btn-success';
            }
            
            // Update main result div too
            const mainResult = document.getElementById('test-result');
            if (mainResult) {
                mainResult.style.display = 'flex';
                mainResult.className = 'alert alert-success';
                mainResult.innerHTML = '<svg class="alert-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>Payment gateway is fully configured and working! Test payment of ₹1.00 was processed and refunded successfully.</span>';
            }
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        if (errorDiv) {
            errorDiv.textContent = error.message;
            errorDiv.style.display = 'block';
        }
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg> Process Test Payment';
        }
    });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Test connection button
    const testBtn = document.getElementById('test-connection-btn');
    if (testBtn) {
        testBtn.addEventListener('click', testConnection);
    }
    
    // Modal buttons
    const closeBtn = document.getElementById('modal-close-btn');
    const cancelBtn = document.getElementById('modal-cancel-btn');
    const processBtn = document.getElementById('process-test-payment-btn');
    
    if (closeBtn) closeBtn.addEventListener('click', closeTestPaymentModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeTestPaymentModal);
    if (processBtn) processBtn.addEventListener('click', processTestPayment);
    
    // Close modal on outside click
    const modal = document.getElementById('test-payment-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeTestPaymentModal();
            }
        });
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
.form-group{margin-bottom:var(--spacing-4)}
.form-label{display:block;font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);color:var(--color-gray-700);margin-bottom:var(--spacing-2)}
.form-input{width:100%;padding:var(--spacing-2) var(--spacing-3);border:1px solid var(--color-gray-300);border-radius:var(--radius-md);font-size:var(--font-size-base);color:var(--color-gray-900)}
.form-input:focus{outline:none;border-color:var(--color-primary);box-shadow:0 0 0 3px rgba(59,130,246,0.1)}
.form-help{font-size:var(--font-size-sm);color:var(--color-gray-600);margin:var(--spacing-1) 0 0 0}
.radio-group{display:flex;flex-direction:column;gap:1rem}
.radio-label{display:flex;align-items:flex-start;gap:0.75rem;padding:1rem;border:2px solid var(--color-gray-200);border-radius:var(--radius-lg);cursor:pointer;transition:all 0.2s ease}
.radio-label:hover{border-color:var(--color-primary);background-color:var(--color-gray-50)}
.radio-input{margin-top:0.25rem;width:1.25rem;height:1.25rem;cursor:pointer}
.radio-input:checked + .radio-text{color:var(--color-primary)}
.radio-label:has(.radio-input:checked){border-color:var(--color-primary);background-color:#eff6ff}
.radio-text{display:flex;flex-direction:column;gap:0.25rem}
.radio-text strong{font-size:var(--font-size-base);font-weight:var(--font-weight-semibold)}
.radio-text small{font-size:var(--font-size-sm);color:var(--color-gray-600)}
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
.alert ol{line-height:1.6;margin:0.5rem 0 0 1.5rem}
.alert ol li{margin-bottom:0.25rem}

.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:1rem;animation:fadeIn 0.2s ease-in-out}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal-content{background:white;border-radius:var(--radius-xl);max-width:500px;width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04);animation:slideUp 0.3s ease-out}
@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:var(--spacing-5);border-bottom:1px solid var(--color-gray-200)}
.modal-title{display:flex;align-items:center;gap:var(--spacing-3);font-size:var(--font-size-xl);font-weight:var(--font-weight-semibold);margin:0;color:var(--color-gray-900)}
.modal-icon{width:24px;height:24px;color:var(--color-primary)}
.modal-close{background:none;border:none;cursor:pointer;padding:var(--spacing-2);border-radius:var(--radius-md);color:var(--color-gray-500);transition:all 0.2s ease}
.modal-close:hover{background-color:var(--color-gray-100);color:var(--color-gray-700)}
.modal-body{padding:var(--spacing-5)}
.modal-description{color:var(--color-gray-600);margin-bottom:var(--spacing-4);line-height:1.6}
.modal-footer{display:flex;justify-content:flex-end;gap:var(--spacing-3);padding:var(--spacing-5);border-top:1px solid var(--color-gray-200);background-color:var(--color-gray-50);border-radius:0 0 var(--radius-xl) var(--radius-xl)}
.test-payment-info{background-color:var(--color-gray-50);border-radius:var(--radius-lg);padding:1rem;margin-bottom:1rem}
.info-item{display:flex;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid var(--color-gray-200)}
.info-item:last-child{border-bottom:none}
.info-label{color:var(--color-gray-600);font-weight:500}
.info-value{color:var(--color-gray-900);font-weight:600}
.spin{animation:spin 1s linear infinite}
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
.btn-success{background-color:#10b981;color:white;border:none}
.btn-success:hover{background-color:#059669}
@media(max-width:768px){.admin-page-header{flex-direction:column}.modal-content{width:95%}.modal-footer{flex-direction:column-reverse}.modal-footer .btn{width:100%}}
</style>

<?php
// Include admin footer
include __DIR__ . '/../templates/admin-footer.php';
?>
