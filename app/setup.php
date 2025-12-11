<?php
/**
 * Customer Setup Instructions Page
 * Display setup guide for assigned port
 * 
 * Displays:
 * - Assigned port URL
 * - Step-by-step setup guide
 * 
 * Requirements: 5.4
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
$portUrl = '';
$portNumber = '';
$fullPortAddress = '';
$planName = '';
$serverRegion = '';

// If active subscription exists, fetch port details
if ($activeSubscription) {
    $hasActiveSubscription = true;
    
    // Fetch plan details
    $planModel = new Plan();
    $plan = $planModel->findById($activeSubscription['plan_id']);
    
    if ($plan) {
        $planName = htmlspecialchars($plan['name']);
    }
    
    // Fetch port details if assigned
    if ($activeSubscription['assigned_port_id']) {
        $portModel = new Port();
        $port = $portModel->findById($activeSubscription['assigned_port_id']);
        
        if ($port) {
            $hasAssignedPort = true;
            $portUrl = htmlspecialchars($port['instance_url']);
            $portNumber = $port['port_number'] ? htmlspecialchars($port['port_number']) : '';
            $serverRegion = htmlspecialchars($port['server_region'] ?? 'Default');
            
            // Build full port address
            $fullPortAddress = $portUrl;
            if ($portNumber) {
                $fullPortAddress .= ':' . $portNumber;
            }
        }
    }
}

// Set page variables
$page_title = 'Setup Instructions';

// Include customer portal header
require_once __DIR__ . '/../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Setup Instructions</h2>
    <p style="color: #666; margin-top: 0.5rem;">
        Follow these steps to get started with your Karyalay instance
    </p>
</div>

<?php if (!$hasActiveSubscription): ?>
    <div class="info-box">
        <div class="info-box-content">
            <p style="text-align: center; padding: 2rem 0;">
                You don't have an active subscription yet.
            </p>
            <div style="text-align: center;">
                <a href="<?php echo get_base_url(); ?>/pricing.php" class="btn btn-primary">View Plans</a>
            </div>
        </div>
    </div>
<?php elseif (!$hasAssignedPort): ?>
    <div class="info-box">
        <div class="info-box-content">
            <p style="text-align: center; padding: 2rem 0;">
                Your instance is being set up. Port allocation is pending.<br>
                You will receive an email notification once your instance is ready.
            </p>
            <div style="text-align: center; margin-top: 1rem;">
                <a href="<?php echo get_app_base_url(); ?>/app/dashboard.php" class="btn btn-outline">Back to Dashboard</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Instance Details -->
    <div class="info-box">
        <h3 class="info-box-title">Your Instance Details</h3>
        <div class="info-box-content">
            <div class="info-box-row">
                <span class="info-box-label">Plan</span>
                <span class="info-box-value"><?php echo $planName; ?></span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Instance URL</span>
                <span class="info-box-value">
                    <code style="background: #f5f5f5; padding: 0.25rem 0.5rem; border-radius: 4px; font-family: monospace;">
                        <?php echo $fullPortAddress; ?>
                    </code>
                    <button onclick="copyToClipboard('<?php echo $fullPortAddress; ?>')" class="btn-copy" title="Copy to clipboard">
                        Copy
                    </button>
                </span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Server Region</span>
                <span class="info-box-value"><?php echo $serverRegion; ?></span>
            </div>
        </div>
    </div>

    <!-- Setup Steps -->
    <div class="setup-steps">
        <div class="setup-step">
            <div class="step-number">1</div>
            <div class="step-content">
                <h3 class="step-title">Access Your Instance</h3>
                <p class="step-description">
                    Open your web browser and navigate to your instance URL:
                </p>
                <div class="code-block">
                    <code><?php echo $fullPortAddress; ?></code>
                </div>
                <p class="step-note">
                    <strong>Note:</strong> Make sure you're using HTTPS if your instance URL starts with https://
                </p>
            </div>
        </div>

        <div class="setup-step">
            <div class="step-number">2</div>
            <div class="step-content">
                <h3 class="step-title">Initial Login</h3>
                <p class="step-description">
                    When you first access your instance, you'll be prompted to create an admin account. Use the following credentials:
                </p>
                <ul class="step-list">
                    <li>Choose a strong username</li>
                    <li>Create a secure password (minimum 8 characters)</li>
                    <li>Provide your email address for notifications</li>
                </ul>
                <p class="step-note">
                    <strong>Important:</strong> Keep your credentials secure and don't share them with anyone.
                </p>
            </div>
        </div>

        <div class="setup-step">
            <div class="step-number">3</div>
            <div class="step-content">
                <h3 class="step-title">Configure Your Settings</h3>
                <p class="step-description">
                    After logging in, configure your instance settings:
                </p>
                <ul class="step-list">
                    <li>Set up your organization details</li>
                    <li>Configure email notifications</li>
                    <li>Customize your workspace preferences</li>
                    <li>Add team members if needed</li>
                </ul>
            </div>
        </div>

        <div class="setup-step">
            <div class="step-number">4</div>
            <div class="step-content">
                <h3 class="step-title">Start Using Karyalay</h3>
                <p class="step-description">
                    You're all set! Start exploring the features included in your <?php echo $planName; ?> plan:
                </p>
                <ul class="step-list">
                    <li>Create and manage your projects</li>
                    <li>Collaborate with your team</li>
                    <li>Track your progress and analytics</li>
                    <li>Customize workflows to fit your needs</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Additional Resources -->
    <div class="info-box">
        <h3 class="info-box-title">Need Help?</h3>
        <div class="info-box-content">
            <p style="margin-bottom: 1rem;">
                If you encounter any issues during setup or have questions, we're here to help:
            </p>
            <div class="quick-actions">
                <a href="<?php echo get_app_base_url(); ?>/app/support/tickets.php" class="btn btn-primary">
                    Create Support Ticket
                </a>
                <a href="<?php echo get_base_url(); ?>/support.php" class="btn btn-outline">
                    View FAQ
                </a>
                <a href="<?php echo get_app_base_url(); ?>/app/subscription.php" class="btn btn-outline">
                    View Subscription
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.setup-steps {
    margin: 2rem 0;
}

.setup-step {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: white;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.step-number {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: var(--primary-color, #007bff);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}

.step-content {
    flex: 1;
}

.step-title {
    margin: 0 0 0.75rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
}

.step-description {
    margin: 0 0 1rem 0;
    color: #555;
    line-height: 1.6;
}

.step-list {
    margin: 1rem 0;
    padding-left: 1.5rem;
    color: #555;
}

.step-list li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.step-note {
    margin: 1rem 0 0 0;
    padding: 0.75rem;
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    border-radius: 4px;
    color: #856404;
    font-size: 0.9rem;
}

.code-block {
    background: #f5f5f5;
    padding: 1rem;
    border-radius: 4px;
    margin: 1rem 0;
    overflow-x: auto;
}

.code-block code {
    font-family: 'Courier New', monospace;
    color: #333;
    font-size: 0.95rem;
}

.btn-copy {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
    padding: 0.25rem 0.5rem;
    margin-left: 0.5rem;
    transition: transform 0.2s;
}

.btn-copy:hover {
    transform: scale(1.2);
}

@media (max-width: 768px) {
    .setup-step {
        flex-direction: column;
        gap: 1rem;
    }
    
    .step-number {
        align-self: flex-start;
    }
}
</style>

<script>
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Copied to clipboard: ' + text);
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
        alert('Copied to clipboard: ' + text);
    } catch (err) {
        console.error('Failed to copy: ', err);
        alert('Failed to copy to clipboard');
    }
    document.body.removeChild(textArea);
}
</script>

<?php
// Include customer portal footer
require_once __DIR__ . '/../templates/customer-footer.php';
?>
