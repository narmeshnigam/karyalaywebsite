<?php
/**
 * Customer Dashboard
 * Main dashboard page for authenticated customers
 * 
 * Displays:
 * - Active plan name, expiry date, assigned port address
 * - Quick links to setup, subscription, support
 * - Support ticket count
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Include required files
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Models\Port;
use Karyalay\Models\User;
use Karyalay\Models\Ticket;

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

// Fetch ticket statistics
$ticketModel = new Ticket();
$allTickets = $ticketModel->findByCustomerId($userId);
$openTickets = array_filter($allTickets, fn($t) => in_array($t['status'], ['OPEN', 'IN_PROGRESS', 'WAITING_ON_CUSTOMER']));
$openTicketCount = count($openTickets);
$totalTicketCount = count($allTickets);

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
<div class="dashboard-cards">
    <!-- Active Subscription Card -->
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h3 class="dashboard-card-title">Active Plan</h3>
        </div>
        <p class="dashboard-card-value"><?php echo $planName; ?></p>
        <p class="dashboard-card-description">
            <?php if ($hasActiveSubscription): ?>
                Expires on <?php echo $expiryDate; ?>
            <?php else: ?>
                No active subscription
            <?php endif; ?>
        </p>
        <a href="<?php echo get_app_base_url(); ?>/app/subscription.php" class="dashboard-card-link">
            View Details →
        </a>
    </div>

    <!-- Port Information Card -->
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h3 class="dashboard-card-title">Your Instance</h3>
        </div>
        <p class="dashboard-card-value">
            <?php echo $hasActiveSubscription ? 'Active' : 'Inactive'; ?>
        </p>
        <p class="dashboard-card-description"><?php echo $portAddress; ?></p>
        <?php if ($hasActiveSubscription && $portAddress !== 'Not Assigned'): ?>
            <a href="<?php echo get_app_base_url(); ?>/app/setup.php" class="dashboard-card-link">
                Setup Guide →
            </a>
        <?php else: ?>
            <a href="<?php echo get_base_url(); ?>/pricing.php" class="dashboard-card-link">
                Get Started →
            </a>
        <?php endif; ?>
    </div>

    <!-- Support Tickets Card -->
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <h3 class="dashboard-card-title">Support Tickets</h3>
        </div>
        <p class="dashboard-card-value">
            <?php if ($openTicketCount > 0): ?>
                <span class="ticket-count-badge"><?php echo $openTicketCount; ?> Open</span>
            <?php else: ?>
                <?php echo $totalTicketCount; ?>
            <?php endif; ?>
        </p>
        <p class="dashboard-card-description">
            <?php if ($totalTicketCount === 0): ?>
                No tickets yet
            <?php elseif ($openTicketCount > 0): ?>
                <?php echo $totalTicketCount; ?> total ticket<?php echo $totalTicketCount > 1 ? 's' : ''; ?>
            <?php else: ?>
                All tickets resolved
            <?php endif; ?>
        </p>
        <a href="<?php echo get_app_base_url(); ?>/app/support/tickets.php" class="dashboard-card-link">
            View Tickets →
        </a>
    </div>
</div>

<!-- Quick Actions -->
<div class="section-header" style="margin-top: 2rem;">
    <h2 class="section-title">Quick Actions</h2>
</div>

<div class="quick-actions">
    <a href="<?php echo get_app_base_url(); ?>/app/subscription.php" class="btn btn-outline">
        Manage Subscription
    </a>
    <a href="<?php echo get_app_base_url(); ?>/app/billing/history.php" class="btn btn-outline">
        View Billing
    </a>
    <a href="<?php echo get_app_base_url(); ?>/app/support/tickets/new.php" class="btn btn-outline">
        New Ticket
    </a>
    <?php if ($hasActiveSubscription && $portAddress !== 'Not Assigned'): ?>
        <a href="<?php echo get_app_base_url(); ?>/app/setup.php" class="btn btn-outline">
            Setup Guide
        </a>
    <?php else: ?>
        <a href="<?php echo get_base_url(); ?>/pricing.php" class="btn btn-primary">
            View Plans
        </a>
    <?php endif; ?>
</div>

<!-- Account Info -->
<div class="section-header" style="margin-top: 2rem;">
    <h2 class="section-title">Account Information</h2>
</div>

<div class="info-box">
    <div class="info-box-content">
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
        <div class="info-box-row">
            <span class="info-box-label">Email</span>
            <span class="info-box-value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
        </div>
    </div>
</div>

<style>
/* Dashboard Cards */
.dashboard-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.dashboard-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    transition: box-shadow 0.2s, border-color 0.2s;
}

.dashboard-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    border-color: #d1d5db;
}

.dashboard-card-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.dashboard-card-icon {
    font-size: 1.5rem;
}

.dashboard-card-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.dashboard-card-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.dashboard-card-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0 0 1rem 0;
}

.dashboard-card-link {
    font-size: 0.875rem;
    font-weight: 500;
    color: #2563eb;
    text-decoration: none;
}

.dashboard-card-link:hover {
    text-decoration: underline;
}

/* Ticket Count Badge */
.ticket-count-badge {
    display: inline-block;
    background: #fef3c7;
    color: #92400e;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 1rem;
    font-weight: 600;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.quick-actions .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 640px) {
    .dashboard-cards {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        flex-direction: column;
    }
    
    .quick-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php
// Include customer portal footer
require_once __DIR__ . '/../templates/customer-footer.php';
?>
