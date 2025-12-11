<?php
/**
 * Subscription Renewal Checkout Page
 * Displays current subscription details and renewal checkout
 */

// Load Composer autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../../config/app.php';

// Set error reporting based on environment
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load authentication helpers
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/template_helpers.php';

// Guard customer portal - requires authentication
$user = guardCustomerPortal();

// Database connection
use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;
use Karyalay\Services\RenewalService;

$error = null;
$subscription = null;
$plan = null;
$renewalDetails = null;

try {
    // Get subscription ID from query parameter
    if (!isset($_GET['subscription_id'])) {
        $_SESSION['flash_message'] = 'No subscription specified for renewal.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . get_app_base_url() . '/app/subscription.php');
        exit;
    }
    
    $subscriptionId = $_GET['subscription_id'];
    
    $subscriptionModel = new Subscription();
    $planModel = new Plan();
    $renewalService = new RenewalService();
    
    // Fetch subscription
    $subscription = $subscriptionModel->findById($subscriptionId);
    
    if (!$subscription) {
        $_SESSION['flash_message'] = 'Subscription not found.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . get_app_base_url() . '/app/subscription.php');
        exit;
    }
    
    // Verify subscription belongs to current user
    if ($subscription['customer_id'] !== $user['id']) {
        $_SESSION['flash_message'] = 'Unauthorized access to subscription.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . get_app_base_url() . '/app/subscription.php');
        exit;
    }
    
    // Check if subscription is eligible for renewal
    if (!$renewalService->isEligibleForRenewal($subscriptionId)) {
        $_SESSION['flash_message'] = 'This subscription is not eligible for renewal.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: ' . get_app_base_url() . '/app/subscription.php');
        exit;
    }
    
    // Get renewal details
    $renewalDetails = $renewalService->getRenewalDetails($subscriptionId);
    
    if (!$renewalDetails) {
        $_SESSION['flash_message'] = 'Unable to calculate renewal details.';
        $_SESSION['flash_type'] = 'danger';
        header('Location: ' . get_app_base_url() . '/app/subscription.php');
        exit;
    }
    
    $plan = $renewalDetails['plan'];
    
    if ($plan['status'] !== 'ACTIVE') {
        $_SESSION['flash_message'] = 'This plan is not currently available for renewal.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: ' . get_app_base_url() . '/app/subscription.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log('Renewal checkout page error: ' . $e->getMessage());
    $error = 'An error occurred. Please try again.';
}

// Set page variables
$page_title = 'Renew Subscription';

// Include customer header
require_once __DIR__ . '/../../templates/customer-header.php';
?>

<div class="section-header">
    <h2 class="section-title">Renew Your Subscription</h2>
</div>

<!-- Back Link -->
<div class="quick-actions" style="margin-bottom: 1.5rem;">
    <a href="<?php echo get_app_base_url(); ?>/app/subscription.php" class="btn btn-outline">← Back to Subscription</a>
</div>

<?php if ($error): ?>
    <div class="info-box" style="border-left: 4px solid #ef4444; margin-bottom: 1.5rem;">
        <div class="info-box-content">
            <p style="color: #ef4444; font-weight: 600; margin: 0;"><?php echo htmlspecialchars($error); ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($subscription && $plan && $renewalDetails): ?>
    <!-- Renewal Summary -->
    <div class="info-box">
        <h3 class="info-box-title">Renewal Summary</h3>
        <div class="info-box-content">
            <div class="renewal-plan-header">
                <div>
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; color: #1f2937;">
                        <?php echo htmlspecialchars($plan['name']); ?>
                    </h4>
                    <?php if (!empty($plan['description'])): ?>
                        <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
                            <?php echo htmlspecialchars($plan['description']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="renewal-amount">
                    <span class="renewal-currency"><?php echo htmlspecialchars($renewalDetails['currency']); ?></span>
                    <span class="renewal-price"><?php echo number_format($renewalDetails['renewal_amount'], 2); ?></span>
                </div>
            </div>
            
            <?php if (!empty($plan['features']) && is_array($plan['features'])): ?>
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                    <p style="font-weight: 600; color: #374151; margin-bottom: 0.75rem; font-size: 0.875rem;">Plan Includes:</p>
                    <ul style="margin: 0; padding-left: 1.25rem; color: #6b7280; line-height: 1.8;">
                        <?php foreach (array_slice($plan['features'], 0, 5) as $feature): ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                        <?php endforeach; ?>
                        <?php if (count($plan['features']) > 5): ?>
                            <li style="color: #9ca3af;">+ <?php echo count($plan['features']) - 5; ?> more features</li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Renewal Details -->
    <div class="info-box">
        <h3 class="info-box-title">Renewal Details</h3>
        <div class="info-box-content">
            <div class="info-box-row">
                <span class="info-box-label">Current End Date</span>
                <span class="info-box-value"><?php echo date('M d, Y', strtotime($renewalDetails['current_end_date'])); ?></span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">New End Date</span>
                <span class="info-box-value" style="color: #2563eb; font-weight: 600;">
                    <?php echo date('M d, Y', strtotime($renewalDetails['new_end_date'])); ?>
                </span>
            </div>
            <div class="info-box-row">
                <span class="info-box-label">Extension Period</span>
                <span class="info-box-value">
                    <?php echo $renewalDetails['billing_period_months']; ?> 
                    <?php echo $renewalDetails['billing_period_months'] == 1 ? 'month' : 'months'; ?>
                </span>
            </div>
            <?php if (!empty($subscription['assigned_port_id'])): ?>
                <div class="info-box-row">
                    <span class="info-box-label">Instance Status</span>
                    <span class="info-box-value">
                        <span style="color: #059669;">✓ Your existing port will be preserved</span>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer Information Form -->
    <div class="info-box">
        <h3 class="info-box-title">Confirm Renewal</h3>
        <div class="info-box-content">
            <form method="POST" action="<?php echo get_base_url(); ?>/process-payment.php" id="renewal-form">
                <input type="hidden" name="subscription_id" value="<?php echo htmlspecialchars($subscription['id']); ?>">
                <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plan['id']); ?>">
                <input type="hidden" name="is_renewal" value="1">
                
                <!-- Customer Information -->
                <div class="form-section">
                    <h4 class="form-section-title">Customer Information</h4>
                    
                    <div class="form-row">
                        <div class="form-group form-col">
                            <label for="name" class="form-label">Full Name <span style="color: #ef4444;">*</span></label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                                required
                            >
                        </div>
                        
                        <div class="form-group form-col">
                            <label for="email" class="form-label">Email <span style="color: #ef4444;">*</span></label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                required
                                readonly
                                style="background: #f9fafb;"
                            >
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group form-col">
                            <label for="phone-input" class="form-label">Phone <span style="color: #ef4444;">*</span></label>
                            <?php echo render_phone_input([
                                'id' => 'phone',
                                'name' => 'phone',
                                'value' => $user['phone'] ?? '',
                                'required' => true,
                            ]); ?>
                        </div>
                        
                        <div class="form-group form-col">
                            <label for="business_name" class="form-label">Business Name</label>
                            <input 
                                type="text" 
                                id="business_name" 
                                name="business_name" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($user['business_name'] ?? ''); ?>"
                            >
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="form-section">
                    <h4 class="form-section-title">Payment Method</h4>
                    
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                        <p style="margin: 0; color: #1e40af; font-size: 0.875rem;">
                            <strong>Note:</strong> You will be redirected to our secure payment gateway to complete your renewal.
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input 
                                type="radio" 
                                name="payment_method" 
                                value="card" 
                                checked
                                required
                            >
                            <span style="font-weight: 500;">Credit/Debit Card</span>
                        </label>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="form-group">
                    <label style="display: flex; align-items: start; gap: 0.5rem; cursor: pointer;">
                        <input 
                            type="checkbox" 
                            name="accept_terms" 
                            required
                            style="margin-top: 0.25rem;"
                        >
                        <span style="font-size: 0.875rem; color: #374151;">
                            I agree to the <a href="<?php echo get_base_url(); ?>/terms.php" target="_blank" style="color: #2563eb; text-decoration: underline;">Terms of Service</a> 
                            and <a href="<?php echo get_base_url(); ?>/privacy.php" target="_blank" style="color: #2563eb; text-decoration: underline;">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                
                <!-- Submit Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Proceed to Payment
                    </button>
                    <a href="<?php echo get_app_base_url(); ?>/app/subscription.php" class="btn btn-outline">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Security Notice -->
    <div class="info-box" style="background: #f9fafb;">
        <div class="info-box-content" style="text-align: center;">
            <p style="margin: 0; color: #6b7280; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Secure checkout powered by industry-leading payment gateway
            </p>
        </div>
    </div>
<?php endif; ?>

<style>
/* Renewal Plan Header */
.renewal-plan-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1.5rem;
}

.renewal-amount {
    text-align: right;
    flex-shrink: 0;
}

.renewal-currency {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.renewal-price {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #2563eb;
    line-height: 1;
    margin-top: 0.25rem;
}

/* Form Sections */
.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.form-section:last-of-type {
    border-bottom: none;
    padding-bottom: 0;
}

.form-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 1rem 0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    font-size: 0.9375rem;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9375rem;
    color: #1f2937;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-input:read-only {
    cursor: not-allowed;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

@media (max-width: 640px) {
    .renewal-plan-header {
        flex-direction: column;
    }
    
    .renewal-amount {
        text-align: left;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php
// Include customer footer
require_once __DIR__ . '/../../templates/customer-footer.php';
?>
