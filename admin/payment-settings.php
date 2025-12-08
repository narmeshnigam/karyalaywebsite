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

<div class="admin-content">
    <div class="admin-header">
        <div class="admin-header-content">
            <h1 class="admin-title">
                <svg class="admin-title-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Payment Settings
            </h1>
            <p class="admin-subtitle">Configure payment gateway credentials and settings</p>
        </div>
    </div>

    <div class="admin-main">
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

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Razorpay Configuration</h2>
                <p class="card-description">Enter your Razorpay API credentials to enable payment processing</p>
            </div>

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
                    <a href="/karyalayportal/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

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
</script>

<style>
.radio-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.radio-label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border: 2px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all 0.2s ease;
}

.radio-label:hover {
    border-color: var(--color-primary);
    background-color: var(--color-gray-50);
}

.radio-input {
    margin-top: 0.25rem;
    width: 1.25rem;
    height: 1.25rem;
    cursor: pointer;
}

.radio-input:checked + .radio-text {
    color: var(--color-primary);
}

.radio-label:has(.radio-input:checked) {
    border-color: var(--color-primary);
    background-color: #eff6ff;
}

.radio-text {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.radio-text strong {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
}

.radio-text small {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
}

.input-group {
    display: flex;
    gap: 0.5rem;
}

.input-group .form-input {
    flex: 1;
}

.input-group .btn {
    padding: 0.5rem 1rem;
}

.btn-icon {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.alert-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.admin-title-icon {
    width: 24px;
    height: 24px;
    flex-shrink: 0;
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--color-gray-200);
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin-bottom: 1rem;
}

.alert ol {
    line-height: 1.6;
}

.alert ol li {
    margin-bottom: 0.25rem;
}
</style>

<?php
// Include admin footer
include __DIR__ . '/../templates/admin-footer.php';
?>
