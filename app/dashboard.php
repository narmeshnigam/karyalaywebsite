<?php
/**
 * Customer Dashboard
 * Main dashboard page for authenticated customers
 * 
 * Displays:
 * - Active plan name, expiry date, assigned port address
 * - Quick links to setup, subscription, support
 * 
 * Requirements: 5.1
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../includes/auth_helpers.php';

use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Models\Port;
use Karyalay\Models\User;

// Guard customer portal - requires authentication
$user = guardCustomerPortal();
$userId = $user['id'];

// Fetch active subscription for the customer
$subscriptionModel = new Subscription();
$activeSubscription = $subscriptionModel->findActiveByCustomerId($userId);

// Initialize variables
$planName = 'No Active Plan';
$expiryDate = 'N/A';
$portAddress = 'Not Assigned';
$subscriptionStatus = 'inactive';
$hasActiveSubscription = false;

// If active subscription exists, fetch plan and port details
if ($activeSubscription) {
    $hasActiveSubscription = true;
    $subscriptionStatus = strtolower($activeSubscription['status']);
    
    // Fetch plan details
    $planModel = new Plan();
    $plan = $planModel->findById($activeSubscription['plan_id']);
    
    if ($plan) {
        $planName = htmlspecialchars($plan['name']);
    }
    
    // Format expiry date
    if ($activeSubscription['end_date']) {
        $expiryDate = date('M d, Y', strtotime($activeSubscription['end_date']));
    }
    
    // Fetch port details if assigned
    if ($activeSubscription['assigned_port_id']) {
        $portModel = new Port();
        $port = $portModel->findById($activeSubscription['assigned_port_id']);
        
        if ($port) {
            $portAddress = htmlspecialchars($port['instance_url']);
            if ($port['port_number']) {
                $portAddress .= ':' . htmlspecialchars($port['port_number']);
            }
        }
    }
}

// Get user details for display
$userName = htmlspecialchars($user['name'] ?? 'User');
$memberSince = isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A';

// Set page variables
$page_title = 'Dashboard';

// Include customer portal header
require_once __DIR__ . '/../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Welcome back, <?php echo $userName; ?>!</h2>
</div>

<!-- Dashboard Cards Grid -->
<div class="customer-portal-dashboard-grid">
    <!-- Active Subscription Card -->
    <div class="customer-portal-card">
        <div class="customer-portal-card-header">
            <h3 class="customer-portal-card-title">Active Plan</h3>
            <span class="customer-portal-card-icon">📦</span>
        </div>
        <p class="customer-portal-card-value"><?php echo $planName; ?></p>
        <p class="customer-portal-card-description">
            <?php if ($hasActiveSubscription): ?>
                Expires on <?php echo $expiryDate; ?>
            <?php else: ?>
                No active subscription
            <?php endif; ?>
        </p>
        <a href="<?php echo get_base_url(); ?>/app/subscription.php" class="customer-portal-card-link">
            View Details →
        </a>
    </div>

    <!-- Port Information Card -->
    <div class="customer-portal-card">
        <div class="customer-portal-card-header">
            <h3 class="customer-portal-card-title">Your Instance</h3>
            <span class="customer-portal-card-icon">🔌</span>
        </div>
        <p class="customer-portal-card-value">
            <?php echo $hasActiveSubscription ? 'Active' : 'Inactive'; ?>
        </p>
        <p class="customer-portal-card-description"><?php echo $portAddress; ?></p>
        <?php if ($hasActiveSubscription && $portAddress !== 'Not Assigned'): ?>
            <a href="<?php echo get_base_url(); ?>/app/setup.php" class="customer-portal-card-link">
                Setup Guide →
            </a>
        <?php else: ?>
            <a href="<?php echo get_base_url(); ?>/pricing.php" class="customer-portal-card-link">
                Get Started →
            </a>
        <?php endif; ?>
    </div>

    <!-- Support Tickets Card -->
    <div class="customer-portal-card">
        <div class="customer-portal-card-header">
            <h3 class="customer-portal-card-title">Support Tickets</h3>
            <span class="customer-portal-card-icon">🎫</span>
        </div>
        <p class="customer-portal-card-value">-</p>
        <p class="customer-portal-card-description">Support available</p>
        <a href="<?php echo get_base_url(); ?>/app/support/tickets.php" class="customer-portal-card-link">
            View Tickets →
        </a>
    </div>
</div>

<!-- Quick Actions -->
<div class="section-header">
    <h2 class="section-title">Quick Actions</h2>
</div>

<div class="quick-actions">
    <a href="<?php echo get_base_url(); ?>/app/subscription.php" class="quick-action-btn">
        <span class="quick-action-icon">📦</span>
        <span class="quick-action-text">Manage Subscription</span>
    </a>
    <a href="<?php echo get_base_url(); ?>/app/billing/history.php" class="quick-action-btn">
        <span class="quick-action-icon">💳</span>
        <span class="quick-action-text">View Billing</span>
    </a>
    <a href="<?php echo get_base_url(); ?>/app/support/tickets.php" class="quick-action-btn">
        <span class="quick-action-icon">🎫</span>
        <span class="quick-action-text">View Tickets</span>
    </a>
    <?php if ($hasActiveSubscription && $portAddress !== 'Not Assigned'): ?>
        <a href="<?php echo get_base_url(); ?>/app/setup.php" class="quick-action-btn">
            <span class="quick-action-icon">📖</span>
            <span class="quick-action-text">Setup Guide</span>
        </a>
    <?php else: ?>
        <a href="<?php echo get_base_url(); ?>/pricing.php" class="quick-action-btn">
            <span class="quick-action-icon">🛒</span>
            <span class="quick-action-text">View Plans</span>
        </a>
    <?php endif; ?>
</div>

<!-- Recent Activity -->
<div class="section-header" style="margin-top: 3rem;">
    <h2 class="section-title">Recent Activity</h2>
</div>

<div class="info-box">
    <div class="info-box-content">
        <div class="info-box-row">
            <span class="info-box-label">Last Login</span>
            <span class="info-box-value"><?php echo date('M d, Y g:i A'); ?></span>
        </div>
        <div class="info-box-row">
            <span class="info-box-label">Account Status</span>
            <span class="info-box-value">
                <span class="subscription-status <?php echo $subscriptionStatus; ?>">
                    <?php echo ucfirst($subscriptionStatus); ?>
                </span>
            </span>
        </div>
        <div class="info-box-row">
            <span class="info-box-label">Member Since</span>
            <span class="info-box-value"><?php echo $memberSince; ?></span>
        </div>
    </div>
</div>

<?php
// Include customer portal footer
require_once __DIR__ . '/../templates/customer-footer.php';
?>
