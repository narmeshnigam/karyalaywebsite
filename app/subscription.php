<?php
/**
 * Customer Subscription Page
 * View and manage subscription details
 * 
 * Displays:
 * - Current plan, start date, end date, status, port details
 * - Renewal button
 * 
 * Requirements: 5.2
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
$planName = 'No Active Plan';
$planDescription = '';
$planPrice = '';
$planCurrency = '';
$billingPeriod = '';
$subscriptionStatus = 'inactive';
$startDate = 'N/A';
$endDate = 'N/A';
$portUrl = 'Not Assigned';
$portNumber = '';
$serverRegion = 'N/A';
$assignedDate = 'N/A';
$planFeatures = [];

// If active subscription exists, fetch plan and port details
if ($activeSubscription) {
    $hasActiveSubscription = true;
    $subscriptionStatus = strtolower($activeSubscription['status']);
    
    // Fetch plan details
    $planModel = new Plan();
    $plan = $planModel->findById($activeSubscription['plan_id']);
    
    if ($plan) {
        $planName = htmlspecialchars($plan['name']);
        $planDescription = htmlspecialchars($plan['description'] ?? '');
        $effectivePrice = !empty($plan['discounted_price']) && $plan['discounted_price'] > 0 
            ? $plan['discounted_price'] 
            : $plan['mrp'];
        $planPrice = format_price($effectivePrice);
        $planCurrency = get_currency_symbol();
        $billingPeriod = $plan['billing_period_months'] . ' month' . ($plan['billing_period_months'] > 1 ? 's' : '');
        $planFeatures = $plan['features'] ?? [];
    }
    
    // Format dates
    if ($activeSubscription['start_date']) {
        $startDate = date('M d, Y', strtotime($activeSubscription['start_date']));
    }
    
    if ($activeSubscription['end_date']) {
        $endDate = date('M d, Y', strtotime($activeSubscription['end_date']));
    }
    
    // Fetch port details if assigned
    if ($activeSubscription['assigned_port_id']) {
        $portModel = new Port();
        $port = $portModel->findById($activeSubscription['assigned_port_id']);
        
        if ($port) {
            $portUrl = htmlspecialchars($port['instance_url']);
            $portNumber = $port['port_number'] ? htmlspecialchars($port['port_number']) : '';
            $serverRegion = htmlspecialchars($port['server_region'] ?? 'N/A');
            
            if ($port['assigned_at']) {
                $assignedDate = date('M d, Y', strtotime($port['assigned_at']));
            }
        }
    }
}

// Set page variables
$page_title = 'Subscription';

// Include customer portal header
require_once __DIR__ . '/../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Subscription Details</h2>
</div>

<?php if (!$hasActiveSubscription): ?>
    <div class="info-box">
        <div class="info-box-content">
            <p style="text-align: center; padding: 2rem 0;">
                You don't have an active subscription yet.
            </p>
            <div style="text-align: center;">
                <a href="<?php echo get_app_base_url(); ?>/app/plans.php" class="btn btn-primary">Get Started</a>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="info-box">
        <h3 class="info-box-title">Current Plan</h3>
        <div class="info-box-content">
            <div class="info-box-row">
                <span class="info-box-label">Plan Name</span>
                <span class="info-box-value"><?php echo $planName; ?></span>
            </div>
            <?php if ($planDescription): ?>
                <div class="info-box-row">
                    <span class="info-box-label">Description</span>
                    <span class="info-box-value"><?php echo $planDescription; ?></span>
                </div>
            <?php endif; ?>
            <div class="info-box-row">
                <span class="info-box-label">Price</span>
                <span class="info-box-value"><?php echo $planCurrency . ' ' . $planPrice; ?></span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Status</span>
                <span class="info-box-value">
                    <span class="subscription-status <?php echo $subscriptionStatus; ?>">
                        <?php echo ucfirst($subscriptionStatus); ?>
                    </span>
                </span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Start Date</span>
                <span class="info-box-value"><?php echo $startDate; ?></span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">End Date</span>
                <span class="info-box-value"><?php echo $endDate; ?></span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Billing Period</span>
                <span class="info-box-value"><?php echo $billingPeriod; ?></span>
            </div>
        </div>
    </div>

    <?php if (!empty($planFeatures)): ?>
        <div class="info-box">
            <h3 class="info-box-title">Plan Features</h3>
            <div class="info-box-content">
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($planFeatures as $feature): ?>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                            ✓ <?php echo htmlspecialchars($feature); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="info-box">
        <h3 class="info-box-title">Assigned Port</h3>
        <div class="info-box-content">
            <?php if ($portUrl !== 'Not Assigned'): ?>
                <div class="info-box-row">
                    <span class="info-box-label">Instance URL</span>
                    <span class="info-box-value"><?php echo $portUrl; ?></span>
                </div>
                <?php if ($portNumber): ?>
                    <div class="info-box-row">
                        <span class="info-box-label">Port Number</span>
                        <span class="info-box-value"><?php echo $portNumber; ?></span>
                    </div>
                <?php endif; ?>
                <div class="info-box-row">
                    <span class="info-box-label">Server Region</span>
                    <span class="info-box-value"><?php echo $serverRegion; ?></span>
                </div>
                <div class="info-box-row">
                    <span class="info-box-label">Assigned On</span>
                    <span class="info-box-value"><?php echo $assignedDate; ?></span>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 1rem 0;">
                    Port allocation is pending. You will be notified once your instance is ready.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="quick-actions">
        <a href="<?php echo get_app_base_url(); ?>/app/subscription/renew.php?subscription_id=<?php echo urlencode($activeSubscription['id']); ?>" class="btn btn-primary">Renew Subscription</a>
        <a href="<?php echo get_app_base_url(); ?>/app/plans.php" class="btn btn-outline">View Other Plans</a>
        <?php if ($portUrl !== 'Not Assigned'): ?>
            <a href="<?php echo get_app_base_url(); ?>/app/setup.php" class="btn btn-outline">Setup Instructions</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
// Include customer portal footer
require_once __DIR__ . '/../templates/customer-footer.php';
?>
