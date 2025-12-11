<?php
/**
 * My Port Page
 * Display assigned port details and setup instructions for the customer
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Models\Port;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$userId = $user['id'];

// Fetch active subscription for the customer
$subscriptionModel = new Subscription();
$activeSubscription = $subscriptionModel->findActiveByCustomerId($userId);

// Initialize variables
$hasActiveSubscription = false;
$hasAssignedPort = false;
$port = null;
$plan = null;

// If active subscription exists, fetch port details
if ($activeSubscription) {
    $hasActiveSubscription = true;
    
    // Fetch plan details
    $planModel = new Plan();
    $plan = $planModel->findById($activeSubscription['plan_id']);
    
    // Fetch port details if assigned
    if ($activeSubscription['assigned_port_id']) {
        $portModel = new Port();
        $port = $portModel->findById($activeSubscription['assigned_port_id']);
        
        if ($port) {
            $hasAssignedPort = true;
        }
    }
}

// Set page variables
$page_title = 'My Port';

// Include customer portal header
require_once __DIR__ . '/../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">My Port</h2>
</div>

<?php if (!$hasActiveSubscription): ?>
    <!-- No Active Subscription -->
    <div class="info-box">
        <div class="port-empty-state">
            <div class="port-empty-icon">🔌</div>
            <h3 class="port-empty-title">No Active Subscription</h3>
            <p class="port-empty-description">
                You don't have an active subscription yet. A port is your dedicated gateway to access all the software features.
            </p>
            
            <div class="port-explanation">
                <h4>What is a Port?</h4>
                <p>
                    A port is your personal instance of the Karyalay software. When you subscribe to a plan, 
                    you get assigned a dedicated port that gives you:
                </p>
                <ul>
                    <li>Your own secure instance URL to access the software</li>
                    <li>Dedicated database for your data</li>
                    <li>Full access to all features included in your plan</li>
                    <li>Isolated environment for your business operations</li>
                </ul>
            </div>
            
            <div class="port-empty-actions">
                <a href="<?php echo get_app_base_url(); ?>/app/plans.php" class="btn btn-primary">View Plans</a>
                <a href="<?php echo get_base_url(); ?>/demo.php" class="btn btn-outline">Request Demo</a>
            </div>
        </div>
    </div>

<?php elseif (!$hasAssignedPort): ?>
    <!-- Subscription Active but No Port Assigned -->
    <div class="info-box">
        <div class="port-empty-state">
            <div class="port-empty-icon">⏳</div>
            <h3 class="port-empty-title">Port Allocation Pending</h3>
            <p class="port-empty-description">
                Your subscription is active, but your port is being set up. You will receive an email notification once your instance is ready.
            </p>
            
            <div class="port-explanation">
                <h4>What happens next?</h4>
                <p>
                    Our team is setting up your dedicated instance. This usually takes a few minutes to a few hours depending on the plan. 
                    Once ready, you'll see all your port details here including:
                </p>
                <ul>
                    <li>Instance URL to access your software</li>
                    <li>Database connection details</li>
                    <li>Setup instructions specific to your instance</li>
                </ul>
            </div>
            
            <div class="port-empty-actions">
                <a href="<?php echo get_app_base_url(); ?>/app/subscription.php" class="btn btn-outline">View Subscription</a>
                <a href="<?php echo get_app_base_url(); ?>/app/support/tickets/new.php" class="btn btn-outline">Contact Support</a>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Port Details -->
    <div class="port-intro-box">
        <p>
            Your port is your dedicated gateway to the Karyalay software. Below you'll find all the details 
            you need to access and configure your instance.
        </p>
    </div>

    <!-- Instance Information -->
    <div class="info-box">
        <h3 class="info-box-title">Instance Information</h3>
        <div class="info-box-content">
            <div class="info-box-row">
                <span class="info-box-label">Instance URL</span>
                <span class="info-box-value">
                    <code class="port-code"><?php echo htmlspecialchars($port['instance_url']); ?></code>
                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($port['instance_url']); ?>')" class="btn-copy" title="Copy">
                        Copy
                    </button>
                </span>
            </div>
            <?php if (!empty($port['port_number'])): ?>
            <div class="info-box-row">
                <span class="info-box-label">Port Number</span>
                <span class="info-box-value">
                    <code class="port-code"><?php echo htmlspecialchars($port['port_number']); ?></code>
                </span>
            </div>
            <?php endif; ?>
            <div class="info-box-row">
                <span class="info-box-label">Server Region</span>
                <span class="info-box-value"><?php echo htmlspecialchars($port['server_region'] ?? 'Default'); ?></span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Status</span>
                <span class="info-box-value">
                    <span class="subscription-status active">Active</span>
                </span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Assigned On</span>
                <span class="info-box-value">
                    <?php echo $port['assigned_at'] ? date('M d, Y', strtotime($port['assigned_at'])) : 'N/A'; ?>
                </span>
            </div>
            <?php if ($plan): ?>
            <div class="info-box-row">
                <span class="info-box-label">Plan</span>
                <span class="info-box-value"><?php echo htmlspecialchars($plan['name']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Database Connection Details (Locked by default) -->
    <?php if (!empty($port['db_host']) || !empty($port['db_name'])): ?>
    <div class="info-box" id="db-credentials-section">
        <h3 class="info-box-title">
            <svg class="lock-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            Database Connection
        </h3>
        
        <!-- Locked State (Default) -->
        <div id="db-locked-state" class="db-locked-state">
            <div class="db-locked-content">
                <div class="db-locked-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="48" height="48">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h4 class="db-locked-title">Credentials Protected</h4>
                <p class="db-locked-description">
                    For security, database credentials are hidden by default. Verify your identity to view them.
                </p>
                <button type="button" class="btn btn-primary" id="btn-unlock-credentials">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                    </svg>
                    Unlock Credentials
                </button>
            </div>
        </div>
        
        <!-- OTP Verification State -->
        <div id="db-otp-state" class="db-otp-state" style="display: none;">
            <div class="db-otp-content">
                <div class="db-otp-header">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="40" height="40">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <h4>Verify Your Identity</h4>
                    <p>We've sent a 6-digit code to <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
                </div>
                
                <div class="db-otp-input-group">
                    <input type="text" class="db-otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="0">
                    <input type="text" class="db-otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="1">
                    <input type="text" class="db-otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="2">
                    <input type="text" class="db-otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="3">
                    <input type="text" class="db-otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="4">
                    <input type="text" class="db-otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="5">
                </div>
                
                <div id="db-otp-error" class="db-otp-error" style="display: none;"></div>
                
                <div class="db-otp-timer">
                    Code expires in <span id="db-otp-countdown">10:00</span>
                </div>
                
                <div class="db-otp-actions">
                    <button type="button" class="btn btn-primary" id="btn-verify-db-otp" disabled>Verify Code</button>
                    <button type="button" class="btn-link" id="btn-resend-db-otp" disabled>
                        Resend Code <span id="db-resend-countdown"></span>
                    </button>
                    <button type="button" class="btn-link" id="btn-cancel-db-otp">Cancel</button>
                </div>
            </div>
        </div>
        
        <!-- Unlocked State (Credentials Visible) -->
        <div id="db-unlocked-state" class="info-box-content" style="display: none;">
            <div class="db-unlocked-notice">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                </svg>
                <span>Credentials unlocked. They will be hidden when you leave this page.</span>
            </div>
            <?php if (!empty($port['db_host'])): ?>
            <div class="info-box-row">
                <span class="info-box-label">Database Host</span>
                <span class="info-box-value">
                    <code class="port-code"><?php echo htmlspecialchars($port['db_host']); ?></code>
                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($port['db_host']); ?>')" class="btn-copy" title="Copy">Copy</button>
                </span>
            </div>
            <?php endif; ?>
            <?php if (!empty($port['db_name'])): ?>
            <div class="info-box-row">
                <span class="info-box-label">Database Name</span>
                <span class="info-box-value">
                    <code class="port-code"><?php echo htmlspecialchars($port['db_name']); ?></code>
                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($port['db_name']); ?>')" class="btn-copy" title="Copy">Copy</button>
                </span>
            </div>
            <?php endif; ?>
            <?php if (!empty($port['db_username'])): ?>
            <div class="info-box-row">
                <span class="info-box-label">Database Username</span>
                <span class="info-box-value">
                    <code class="port-code"><?php echo htmlspecialchars($port['db_username']); ?></code>
                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($port['db_username']); ?>')" class="btn-copy" title="Copy">Copy</button>
                </span>
            </div>
            <?php endif; ?>
            <?php if (!empty($port['db_password'])): ?>
            <div class="info-box-row">
                <span class="info-box-label">Database Password</span>
                <span class="info-box-value">
                    <code class="port-code port-password">••••••••</code>
                    <button onclick="togglePassword(this, '<?php echo htmlspecialchars($port['db_password']); ?>')" class="btn-copy" title="Show/Hide">Show</button>
                    <button onclick="copyToClipboard('<?php echo htmlspecialchars($port['db_password']); ?>')" class="btn-copy" title="Copy">Copy</button>
                </span>
            </div>
            <?php endif; ?>
            <div class="db-lock-again">
                <button type="button" class="btn btn-outline btn-sm" id="btn-lock-credentials">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Lock Credentials
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Setup Instructions -->
    <?php if (!empty($port['setup_instructions'])): ?>
    <div class="info-box">
        <h3 class="info-box-title">Setup Instructions</h3>
        <div class="setup-instructions-content">
            <?php echo $port['setup_instructions']; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="<?php echo htmlspecialchars($port['instance_url']); ?>" target="_blank" class="btn btn-primary">
            Open Instance
        </a>
        <a href="<?php echo get_app_base_url(); ?>/app/subscription.php" class="btn btn-outline">
            View Subscription
        </a>
        <a href="<?php echo get_app_base_url(); ?>/app/support/tickets/new.php" class="btn btn-outline">
            Get Help
        </a>
    </div>
<?php endif; ?>

<style>
/* Port Empty State */
.port-empty-state {
    text-align: center;
    padding: 2rem 1rem;
}

.port-empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.port-empty-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.75rem 0;
}

.port-empty-description {
    font-size: 1rem;
    color: #6b7280;
    margin: 0 0 2rem 0;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.port-explanation {
    background: #f9fafb;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: left;
    max-width: 600px;
    margin: 0 auto 2rem auto;
}

.port-explanation h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.75rem 0;
}

.port-explanation p {
    font-size: 0.9375rem;
    color: #4b5563;
    margin: 0 0 1rem 0;
    line-height: 1.6;
}

.port-explanation ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #4b5563;
}

.port-explanation li {
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.port-empty-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Port Intro Box */
.port-intro-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
}

.port-intro-box p {
    margin: 0;
    color: #1e40af;
    font-size: 0.9375rem;
    line-height: 1.6;
}

/* Port Code Styling */
.port-code {
    font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Courier New', monospace;
    font-size: 0.875rem;
    background: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    color: #1f2937;
}

.port-password {
    letter-spacing: 2px;
}

/* Copy Button */
.btn-copy {
    background: none;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    color: #6b7280;
    cursor: pointer;
    margin-left: 0.5rem;
    transition: all 0.15s ease;
}

.btn-copy:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
    color: #374151;
}

/* Setup Instructions Content */
.setup-instructions-content {
    padding: 1rem 0;
    line-height: 1.7;
    color: #374151;
}

.setup-instructions-content h1,
.setup-instructions-content h2,
.setup-instructions-content h3,
.setup-instructions-content h4 {
    color: #1f2937;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.setup-instructions-content h1:first-child,
.setup-instructions-content h2:first-child,
.setup-instructions-content h3:first-child {
    margin-top: 0;
}

.setup-instructions-content p {
    margin-bottom: 1rem;
}

.setup-instructions-content ul,
.setup-instructions-content ol {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.setup-instructions-content li {
    margin-bottom: 0.5rem;
}

.setup-instructions-content code {
    background: #f3f4f6;
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
    font-family: 'SF Mono', 'Monaco', monospace;
    font-size: 0.875em;
}

.setup-instructions-content pre {
    background: #1f2937;
    color: #f9fafb;
    padding: 1rem;
    border-radius: 6px;
    overflow-x: auto;
    margin-bottom: 1rem;
}

.setup-instructions-content pre code {
    background: none;
    padding: 0;
    color: inherit;
}

.setup-instructions-content a {
    color: #2563eb;
    text-decoration: underline;
}

.setup-instructions-content a:hover {
    color: #1d4ed8;
}

/* Database Credentials Lock Styles */
.lock-icon { display: inline-block; vertical-align: middle; margin-right: 0.5rem; color: #6b7280; }

.db-locked-state, .db-otp-state { padding: 2rem; text-align: center; }

.db-locked-content, .db-otp-content { max-width: 400px; margin: 0 auto; }

.db-locked-icon { color: #9ca3af; margin-bottom: 1rem; }

.db-locked-title { font-size: 1.125rem; font-weight: 600; color: #1f2937; margin: 0 0 0.5rem 0; }

.db-locked-description { color: #6b7280; font-size: 0.9375rem; margin: 0 0 1.5rem 0; line-height: 1.5; }

.db-otp-header { margin-bottom: 1.5rem; }
.db-otp-header svg { color: #667eea; margin-bottom: 0.75rem; }
.db-otp-header h4 { font-size: 1.125rem; font-weight: 600; color: #1f2937; margin: 0 0 0.5rem 0; }
.db-otp-header p { color: #6b7280; font-size: 0.875rem; margin: 0; }
.db-otp-header strong { color: #667eea; }

.db-otp-input-group { display: flex; gap: 0.5rem; justify-content: center; margin-bottom: 1rem; }

.db-otp-input { width: 44px; height: 52px; text-align: center; font-size: 1.25rem; font-weight: 600; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s; }
.db-otp-input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.db-otp-input.filled { border-color: #667eea; background-color: rgba(102, 126, 234, 0.05); }
.db-otp-input.error { border-color: #dc2626; }
.db-otp-input.success { border-color: #059669; background-color: rgba(5, 150, 105, 0.05); }

.db-otp-error { color: #dc2626; font-size: 0.875rem; margin-bottom: 1rem; }

.db-otp-timer { color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem; }
.db-otp-timer span { font-weight: 600; }

.db-otp-actions { display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }

.btn-link { background: none; border: none; color: #667eea; font-size: 0.875rem; cursor: pointer; padding: 0.5rem; }
.btn-link:hover:not(:disabled) { text-decoration: underline; }
.btn-link:disabled { color: #9ca3af; cursor: not-allowed; }

.db-unlocked-notice { display: flex; align-items: center; gap: 0.5rem; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 0.75rem 1rem; margin-bottom: 1rem; color: #065f46; font-size: 0.875rem; }
.db-unlocked-notice svg { color: #059669; flex-shrink: 0; }

.db-lock-again { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; text-align: right; }

.btn-sm { padding: 0.5rem 1rem; font-size: 0.875rem; }

@media (max-width: 640px) {
    .port-empty-actions { flex-direction: column; }
    .port-empty-actions .btn { width: 100%; }
    .info-box-row { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
    .btn-copy { margin-left: 0; margin-top: 0.5rem; }
    .db-otp-input { width: 38px; height: 46px; font-size: 1.125rem; }
}
</style>

<script>
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showCopyFeedback('Copied!');
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.select();
    try {
        document.execCommand('copy');
        showCopyFeedback('Copied!');
    } catch (err) {
        console.error('Failed to copy: ', err);
        showCopyFeedback('Failed to copy');
    }
    document.body.removeChild(textArea);
}

function showCopyFeedback(message) {
    // Create a temporary toast notification
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #1f2937; color: white; padding: 0.75rem 1.5rem; border-radius: 6px; font-size: 0.875rem; z-index: 9999; animation: fadeIn 0.2s ease;';
    document.body.appendChild(toast);
    
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.2s ease';
        setTimeout(function() {
            document.body.removeChild(toast);
        }, 200);
    }, 2000);
}

function togglePassword(button, password) {
    const codeElement = button.parentElement.querySelector('.port-password');
    if (button.textContent === 'Show') {
        codeElement.textContent = password;
        codeElement.classList.remove('port-password');
        button.textContent = 'Hide';
    } else {
        codeElement.textContent = '••••••••';
        codeElement.classList.add('port-password');
        button.textContent = 'Show';
    }
}

// Database Credentials OTP Verification
(function() {
    'use strict';
    
    const baseUrl = '<?php echo get_base_url(); ?>';
    const userEmail = '<?php echo htmlspecialchars($user['email'] ?? ''); ?>';
    
    // Elements
    const lockedState = document.getElementById('db-locked-state');
    const otpState = document.getElementById('db-otp-state');
    const unlockedState = document.getElementById('db-unlocked-state');
    const btnUnlock = document.getElementById('btn-unlock-credentials');
    const btnVerify = document.getElementById('btn-verify-db-otp');
    const btnResend = document.getElementById('btn-resend-db-otp');
    const btnCancel = document.getElementById('btn-cancel-db-otp');
    const btnLock = document.getElementById('btn-lock-credentials');
    const otpInputs = document.querySelectorAll('.db-otp-input');
    const otpError = document.getElementById('db-otp-error');
    const otpCountdown = document.getElementById('db-otp-countdown');
    const resendCountdown = document.getElementById('db-resend-countdown');
    
    let countdownInterval = null;
    let resendInterval = null;
    let otpExpiryTime = null;
    
    if (!btnUnlock) return; // No credentials section
    
    // Unlock button - send OTP
    btnUnlock.addEventListener('click', function() {
        sendDbOtp();
    });
    
    // Verify button
    btnVerify.addEventListener('click', function() {
        verifyDbOtp();
    });
    
    // Resend button
    btnResend.addEventListener('click', function() {
        sendDbOtp();
    });
    
    // Cancel button
    btnCancel.addEventListener('click', function() {
        showLockedState();
    });
    
    // Lock button
    btnLock.addEventListener('click', function() {
        showLockedState();
    });
    
    // OTP input handling
    otpInputs.forEach(function(input, index) {
        input.addEventListener('input', function(e) {
            const value = e.target.value.replace(/[^0-9]/g, '');
            e.target.value = value;
            
            if (value) {
                e.target.classList.add('filled');
                if (index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            } else {
                e.target.classList.remove('filled');
            }
            
            const allFilled = Array.from(otpInputs).every(function(inp) { return inp.value.length === 1; });
            btnVerify.disabled = !allFilled;
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
        
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
            pastedData.split('').forEach(function(char, i) {
                if (otpInputs[i]) {
                    otpInputs[i].value = char;
                    otpInputs[i].classList.add('filled');
                }
            });
            const lastIndex = Math.min(pastedData.length, otpInputs.length) - 1;
            if (lastIndex >= 0) otpInputs[lastIndex].focus();
            btnVerify.disabled = pastedData.length !== 6;
        });
    });
    
    function sendDbOtp() {
        btnUnlock.disabled = true;
        btnUnlock.innerHTML = '<svg class="spinner" viewBox="0 0 24 24" width="18" height="18"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="31.4" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></circle></svg> Sending...';
        
        fetch(baseUrl + '/api/send-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: userEmail, purpose: 'credentials' })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showOtpState(data.expires_in || 600);
            } else {
                alert(data.error || 'Failed to send verification code');
            }
        })
        .catch(function(error) {
            console.error('Send OTP error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(function() {
            btnUnlock.disabled = false;
            btnUnlock.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg> Unlock Credentials';
        });
    }
    
    function verifyDbOtp() {
        const otp = Array.from(otpInputs).map(function(input) { return input.value; }).join('');
        if (otp.length !== 6) return;
        
        btnVerify.disabled = true;
        btnVerify.textContent = 'Verifying...';
        otpError.style.display = 'none';
        
        fetch(baseUrl + '/api/verify-otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: userEmail, otp: otp })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                otpInputs.forEach(function(input) { input.classList.add('success'); });
                setTimeout(function() { showUnlockedState(); }, 500);
            } else {
                otpInputs.forEach(function(input) { input.classList.add('error'); });
                otpError.textContent = data.error || 'Invalid code';
                otpError.style.display = 'block';
                btnVerify.disabled = false;
                btnVerify.textContent = 'Verify Code';
                setTimeout(function() {
                    otpInputs.forEach(function(input) { input.classList.remove('error'); });
                }, 2000);
            }
        })
        .catch(function(error) {
            console.error('Verify OTP error:', error);
            otpError.textContent = 'An error occurred. Please try again.';
            otpError.style.display = 'block';
            btnVerify.disabled = false;
            btnVerify.textContent = 'Verify Code';
        });
    }
    
    function showLockedState() {
        if (countdownInterval) clearInterval(countdownInterval);
        if (resendInterval) clearInterval(resendInterval);
        lockedState.style.display = 'block';
        otpState.style.display = 'none';
        unlockedState.style.display = 'none';
        clearOtpInputs();
    }
    
    function showOtpState(expiresIn) {
        lockedState.style.display = 'none';
        otpState.style.display = 'block';
        unlockedState.style.display = 'none';
        clearOtpInputs();
        otpInputs[0].focus();
        startCountdown(expiresIn);
        startResendCooldown();
    }
    
    function showUnlockedState() {
        if (countdownInterval) clearInterval(countdownInterval);
        if (resendInterval) clearInterval(resendInterval);
        lockedState.style.display = 'none';
        otpState.style.display = 'none';
        unlockedState.style.display = 'block';
    }
    
    function clearOtpInputs() {
        otpInputs.forEach(function(input) {
            input.value = '';
            input.classList.remove('filled', 'error', 'success');
        });
        btnVerify.disabled = true;
        btnVerify.textContent = 'Verify Code';
        otpError.style.display = 'none';
    }
    
    function startCountdown(seconds) {
        if (countdownInterval) clearInterval(countdownInterval);
        otpExpiryTime = Date.now() + (seconds * 1000);
        
        countdownInterval = setInterval(function() {
            const remaining = Math.max(0, Math.floor((otpExpiryTime - Date.now()) / 1000));
            const mins = Math.floor(remaining / 60);
            const secs = remaining % 60;
            otpCountdown.textContent = mins + ':' + secs.toString().padStart(2, '0');
            
            if (remaining <= 0) {
                clearInterval(countdownInterval);
                otpError.textContent = 'Code expired. Please request a new one.';
                otpError.style.display = 'block';
                btnVerify.disabled = true;
            }
        }, 1000);
    }
    
    function startResendCooldown() {
        if (resendInterval) clearInterval(resendInterval);
        let remaining = 60;
        btnResend.disabled = true;
        resendCountdown.textContent = '(' + remaining + 's)';
        
        resendInterval = setInterval(function() {
            remaining--;
            if (remaining > 0) {
                resendCountdown.textContent = '(' + remaining + 's)';
            } else {
                clearInterval(resendInterval);
                resendCountdown.textContent = '';
                btnResend.disabled = false;
            }
        }, 1000);
    }
})();
</script>

<?php
// Include customer portal footer
require_once __DIR__ . '/../templates/customer-footer.php';
?>
