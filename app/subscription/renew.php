<?php

/**
 * Karyalay Portal System
 * Subscription Renewal Checkout Page
 * 
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

// Start secure session
startSecureSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please log in to renew your subscription.';
    $_SESSION['redirect_after_login'] = '/app/subscription/renew.php';
    header('Location: /login.php');
    exit;
}

// Include template helpers
require_once __DIR__ . '/../../includes/template_helpers.php';

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
        $_SESSION['error'] = 'No subscription specified for renewal.';
        header('Location: /app/subscription.php');
        exit;
    }
    
    $subscriptionId = $_GET['subscription_id'];
    
    $subscriptionModel = new Subscription();
    $planModel = new Plan();
    $renewalService = new RenewalService();
    
    // Fetch subscription
    $subscription = $subscriptionModel->findById($subscriptionId);
    
    if (!$subscription) {
        $_SESSION['error'] = 'Subscription not found.';
        header('Location: /app/subscription.php');
        exit;
    }
    
    // Verify subscription belongs to current user
    $currentUser = getCurrentUser();
    if ($subscription['customer_id'] !== $currentUser['id']) {
        $_SESSION['error'] = 'Unauthorized access to subscription.';
        header('Location: /app/subscription.php');
        exit;
    }
    
    // Check if subscription is eligible for renewal
    if (!$renewalService->isEligibleForRenewal($subscriptionId)) {
        $_SESSION['error'] = 'This subscription is not eligible for renewal.';
        header('Location: /app/subscription.php');
        exit;
    }
    
    // Get renewal details
    $renewalDetails = $renewalService->getRenewalDetails($subscriptionId);
    
    if (!$renewalDetails) {
        $_SESSION['error'] = 'Unable to calculate renewal details.';
        header('Location: /app/subscription.php');
        exit;
    }
    
    $plan = $renewalDetails['plan'];
    
    if ($plan['status'] !== 'ACTIVE') {
        $_SESSION['error'] = 'This plan is not currently available for renewal.';
        header('Location: /app/subscription.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log('Renewal checkout page error: ' . $e->getMessage());
    $error = 'An error occurred. Please try again.';
}

// Set page variables
$page_title = 'Renew Subscription';
$page_description = 'Renew your subscription';

// Include customer header
include __DIR__ . '/../../templates/customer-header.php';
?>

<!-- Renewal Checkout Page -->
<section class="section">
    <div class="container">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-6">Renew Your Subscription</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($subscription && $plan && $renewalDetails): ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Renewal Summary -->
                    <div class="lg:col-span-1">
                        <div class="card sticky top-4">
                            <div class="card-body">
                                <h2 class="text-xl font-bold mb-4">Renewal Summary</h2>
                                
                                <div class="mb-4">
                                    <h3 class="font-semibold text-lg">
                                        <?php echo htmlspecialchars($plan['name']); ?>
                                    </h3>
                                    <?php if (!empty($plan['description'])): ?>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <?php echo htmlspecialchars($plan['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($plan['features']) && is_array($plan['features'])): ?>
                                    <div class="mb-4">
                                        <p class="font-semibold text-sm text-gray-700 mb-2">Includes:</p>
                                        <ul class="space-y-1">
                                            <?php foreach (array_slice($plan['features'], 0, 5) as $feature): ?>
                                                <li class="flex items-start gap-2 text-sm">
                                                    <svg class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    <span><?php echo htmlspecialchars($feature); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                            <?php if (count($plan['features']) > 5): ?>
                                                <li class="text-sm text-gray-600">
                                                    + <?php echo count($plan['features']) - 5; ?> more features
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="border-t pt-4 space-y-2">
                                    <div class="flex justify-between items-baseline text-sm">
                                        <span class="text-gray-600">Current End Date:</span>
                                        <span class="font-semibold">
                                            <?php echo date('M d, Y', strtotime($renewalDetails['current_end_date'])); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-baseline text-sm">
                                        <span class="text-gray-600">New End Date:</span>
                                        <span class="font-semibold text-primary">
                                            <?php echo date('M d, Y', strtotime($renewalDetails['new_end_date'])); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-baseline text-sm">
                                        <span class="text-gray-600">Extension Period:</span>
                                        <span class="font-semibold">
                                            <?php echo $renewalDetails['billing_period_months']; ?> 
                                            <?php echo $renewalDetails['billing_period_months'] == 1 ? 'month' : 'months'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="border-t pt-4 mt-4">
                                    <div class="flex justify-between items-baseline">
                                        <span class="text-lg font-semibold">Renewal Amount:</span>
                                        <span class="text-2xl font-bold text-primary">
                                            <?php echo htmlspecialchars($renewalDetails['currency']); ?> 
                                            <?php echo number_format($renewalDetails['renewal_amount'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($subscription['assigned_port_id'])): ?>
                                    <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                                        <p class="text-sm text-blue-800">
                                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Your existing port will be preserved after renewal.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Billing Information Form -->
                    <div class="lg:col-span-2">
                        <div class="card">
                            <div class="card-body">
                                <h2 class="text-xl font-bold mb-6">Confirm Renewal</h2>
                                
                                <form method="POST" action="/process-payment.php" id="renewal-form">
                                    <input type="hidden" name="subscription_id" value="<?php echo htmlspecialchars($subscription['id']); ?>">
                                    <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plan['id']); ?>">
                                    <input type="hidden" name="is_renewal" value="1">
                                    
                                    <!-- Customer Information -->
                                    <div class="mb-6">
                                        <h3 class="font-semibold mb-4">Customer Information</h3>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="form-group">
                                                <label for="name" class="form-label">Full Name *</label>
                                                <input 
                                                    type="text" 
                                                    id="name" 
                                                    name="name" 
                                                    class="form-input" 
                                                    value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>"
                                                    required
                                                >
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="email" class="form-label">Email *</label>
                                                <input 
                                                    type="email" 
                                                    id="email" 
                                                    name="email" 
                                                    class="form-input" 
                                                    value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>"
                                                    required
                                                    readonly
                                                >
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                            <div class="form-group">
                                                <label for="phone" class="form-label">Phone *</label>
                                                <input 
                                                    type="tel" 
                                                    id="phone" 
                                                    name="phone" 
                                                    class="form-input" 
                                                    value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>"
                                                    required
                                                >
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="business_name" class="form-label">Business Name</label>
                                                <input 
                                                    type="text" 
                                                    id="business_name" 
                                                    name="business_name" 
                                                    class="form-input" 
                                                    value="<?php echo htmlspecialchars($currentUser['business_name'] ?? ''); ?>"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Payment Method -->
                                    <div class="mb-6">
                                        <h3 class="font-semibold mb-4">Payment Method</h3>
                                        
                                        <div class="alert alert-info mb-4">
                                            <p class="text-sm">
                                                <strong>Note:</strong> You will be redirected to our secure payment gateway to complete your renewal.
                                            </p>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="flex items-center gap-2">
                                                <input 
                                                    type="radio" 
                                                    name="payment_method" 
                                                    value="card" 
                                                    checked
                                                    required
                                                >
                                                <span>Credit/Debit Card</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Terms and Conditions -->
                                    <div class="mb-6">
                                        <label class="flex items-start gap-2">
                                            <input 
                                                type="checkbox" 
                                                name="accept_terms" 
                                                required
                                                class="mt-1"
                                            >
                                            <span class="text-sm text-gray-700">
                                                I agree to the <a href="/terms.php" target="_blank" class="text-primary underline">Terms of Service</a> 
                                                and <a href="/privacy.php" target="_blank" class="text-primary underline">Privacy Policy</a>
                                            </span>
                                        </label>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="flex gap-4">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            Proceed to Payment
                                        </button>
                                        <a href="/app/subscription.php" class="btn btn-outline btn-lg">
                                            Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Security Notice -->
<section class="section bg-gray-50">
    <div class="container">
        <div class="max-w-4xl mx-auto text-center">
            <div class="flex items-center justify-center gap-2 text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span class="text-sm">Secure checkout powered by industry-leading payment gateway</span>
            </div>
        </div>
    </div>
</section>

<?php
// Include customer footer
include __DIR__ . '/../../templates/customer-footer.php';
?>

