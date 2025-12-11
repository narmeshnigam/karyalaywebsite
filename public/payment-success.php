<?php

/**
 * SellerPortal System
 * Payment Success Page
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Set error reporting based on environment
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load authentication helpers
require_once __DIR__ . '/../includes/auth_helpers.php';

// Include template helpers (needed for get_base_url)
require_once __DIR__ . '/../includes/template_helpers.php';

// Start secure session
startSecureSession();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . get_base_url() . '/login.php');
    exit;
}

use Karyalay\Services\SubscriptionService;

// Get current user
$currentUser = getCurrentUser();
$subscriptionDetails = null;
$portAssigned = false;
$instanceUrl = '';

if ($currentUser) {
    $subscriptionService = new SubscriptionService();
    $subscriptionDetails = $subscriptionService->getActiveSubscriptionForCustomer($currentUser['id']);
    
    if ($subscriptionDetails && $subscriptionDetails['port']) {
        $portAssigned = true;
        $instanceUrl = $subscriptionDetails['port']['instance_url'];
    }
}

// Set page variables
$page_title = 'Payment Successful';
$page_description = 'Your payment was successful';

// Include header
include_header($page_title, $page_description);
?>

<section class="section" style="padding: 4rem 0;">
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto; text-align: center;">
            <div style="margin-bottom: 2rem;">
                <div style="width: 80px; height: 80px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <svg width="40" height="40" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>
            
            <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 1rem; color: #1f2937;">Payment Successful!</h1>
            
            <p style="font-size: 1.1rem; color: #6b7280; margin-bottom: 1.5rem;">
                Thank you for your purchase. Your subscription is now active.
            </p>
            
            <?php if (isset($_GET['payment_id'])): ?>
                <p style="font-size: 0.875rem; color: #9ca3af; margin-bottom: 1.5rem;">
                    Payment ID: <code style="background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 4px;"><?php echo htmlspecialchars($_GET['payment_id']); ?></code>
                </p>
            <?php endif; ?>

            <?php if ($portAssigned && $instanceUrl): ?>
                <div class="alert alert-success" style="margin-bottom: 2rem; text-align: left;">
                    <strong>🎉 Your Instance is Ready!</strong><br>
                    <p style="margin: 0.5rem 0;">Your Karyalay instance has been provisioned:</p>
                    <div style="background: #f0fdf4; padding: 0.75rem; border-radius: 4px; margin-top: 0.5rem; font-family: monospace;">
                        <?php echo htmlspecialchars($instanceUrl); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info" style="margin-bottom: 2rem; text-align: left;">
                    <strong>What's Next?</strong><br>
                    Your instance is being provisioned. You will receive an email with setup instructions shortly.
                </div>
            <?php endif; ?>
            
            <?php if ($subscriptionDetails && $subscriptionDetails['plan']): ?>
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem; text-align: left;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600; color: #374151;">Subscription Summary</h3>
                    <div style="display: grid; gap: 0.5rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6b7280;">Plan</span>
                            <span style="font-weight: 500; color: #111827;"><?php echo htmlspecialchars($subscriptionDetails['plan']['name']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6b7280;">Status</span>
                            <span style="font-weight: 500; color: #059669;">Active</span>
                        </div>
                        <?php if ($subscriptionDetails['subscription']['end_date']): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #6b7280;">Valid Until</span>
                                <span style="font-weight: 500; color: #111827;"><?php echo date('M d, Y', strtotime($subscriptionDetails['subscription']['end_date'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo get_app_base_url(); ?>/app/dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
                <?php if ($portAssigned): ?>
                    <a href="<?php echo get_app_base_url(); ?>/app/my-port.php" class="btn btn-outline btn-lg">View My Port</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include_footer(); ?>
