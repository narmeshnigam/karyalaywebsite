<?php

/**
 * SellerPortal System
 * Checkout Page - Revamped UI matching website theme
 * 
 * Displays selected plan details and billing information form
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

// Start secure session
startSecureSession();

// Include template helpers
require_once __DIR__ . '/../includes/template_helpers.php';

// Check if user is logged in
if (!isAuthenticated()) {
    $_SESSION['error'] = 'Please log in to complete checkout.';
    $_SESSION['redirect_after_login'] = '/checkout.php';
    redirect('/login.php');
}

// Database connection
use Karyalay\Models\Plan;
use Karyalay\Models\BillingAddress;
use Karyalay\Services\PortAvailabilityService;

$error = null;
$plan = null;
$portAvailable = false;
$availablePortsCount = 0;
$billingAddress = null;

try {
    // Check if plan is selected in session
    if (!isset($_SESSION['selected_plan_id'])) {
        $_SESSION['error'] = 'Please select a plan first.';
        redirect('/pricing.php');
    }
    
    $planModel = new Plan();
    $portAvailabilityService = new PortAvailabilityService();
    
    // Fetch selected plan
    $plan = $planModel->findById($_SESSION['selected_plan_id']);
    
    if (!$plan) {
        $_SESSION['error'] = 'Selected plan not found.';
        unset($_SESSION['selected_plan_id']);
        unset($_SESSION['selected_plan_slug']);
        redirect('/pricing.php');
    }
    
    if ($plan['status'] !== 'ACTIVE') {
        $_SESSION['error'] = 'This plan is not currently available.';
        unset($_SESSION['selected_plan_id']);
        unset($_SESSION['selected_plan_slug']);
        redirect('/pricing.php');
    }
    
    // Check port availability using service (plan-agnostic)
    $availabilityCheck = $portAvailabilityService->checkAvailability();
    $portAvailable = $availabilityCheck['available'];
    $availablePortsCount = $availabilityCheck['count'];
    
    // Get current user
    $currentUser = getCurrentUser();
    
    // Load existing billing address if available
    $billingAddressModel = new BillingAddress();
    $billingAddress = $billingAddressModel->findByCustomerId($currentUser['id']);
    
} catch (Exception $e) {
    error_log('Checkout page error: ' . $e->getMessage());
    $error = 'An error occurred. Please try again.';
    $currentUser = getCurrentUser();
}

// Set page variables
$page_title = 'Checkout';
$page_description = 'Complete your purchase';

// Include header
include_header($page_title, $page_description);
?>

<!-- Checkout Hero Section -->
<section class="checkout-hero">
    <div class="container">
        <div class="checkout-hero-content">
            <span class="checkout-hero-label">Secure Checkout</span>
            <h1 class="checkout-hero-title">Complete Your Purchase</h1>
            <p class="checkout-hero-subtitle">You're just one step away from transforming your business</p>
        </div>
    </div>
</section>

<!-- Checkout Content Section -->
<section class="checkout-content-section">
    <div class="container">
        <?php if ($error): ?>
            <div class="checkout-alert checkout-alert-danger" role="alert">
                <svg class="checkout-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="checkout-alert-content">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="checkout-alert checkout-alert-danger" role="alert">
                <svg class="checkout-alert-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="checkout-alert-content">
                    <strong>Error:</strong> <?php echo $_SESSION['error']; ?>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (!$portAvailable): ?>
            <!-- No Ports Available State -->
            <div class="checkout-unavailable-card">
                <div class="checkout-unavailable-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="checkout-unavailable-title">No Available Ports</h2>
                <p class="checkout-unavailable-text">
                    Unfortunately, there are no available ports at the moment. 
                    Our team has been notified and will provision additional capacity soon.
                </p>
                <p class="checkout-unavailable-contact">
                    Please contact us at <a href="mailto:<?php echo htmlspecialchars($config['admin_email'] ?? 'admin@karyalay.com'); ?>">
                        <?php echo htmlspecialchars($config['admin_email'] ?? 'admin@karyalay.com'); ?>
                    </a> for more information.
                </p>
                <div class="checkout-unavailable-actions">
                    <a href="<?php echo get_base_url(); ?>/pricing.php" class="btn btn-primary">Back to Pricing</a>
                    <a href="<?php echo get_base_url(); ?>/contact.php" class="btn btn-outline">Contact Us</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($plan && $portAvailable): ?>
            <div class="checkout-grid">
                <!-- Order Summary Sidebar -->
                <aside class="checkout-sidebar">
                    <div class="checkout-summary-card">
                        <div class="checkout-summary-header">
                            <h2 class="checkout-summary-title">Order Summary</h2>
                        </div>
                        
                        <div class="checkout-plan-info">
                            <span class="checkout-plan-badge">Selected Plan</span>
                            <h3 class="checkout-plan-name"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <?php if (!empty($plan['description'])): ?>
                                <p class="checkout-plan-desc"><?php echo htmlspecialchars($plan['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($plan['features']) && is_array($plan['features'])): ?>
                            <div class="checkout-features">
                                <p class="checkout-features-title">What's Included:</p>
                                <ul class="checkout-features-list">
                                    <?php foreach (array_slice($plan['features'], 0, 5) as $feature): ?>
                                        <li class="checkout-feature-item">
                                            <svg class="checkout-feature-check" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span><?php echo htmlspecialchars($feature); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (count($plan['features']) > 5): ?>
                                        <li class="checkout-feature-more">+ <?php echo count($plan['features']) - 5; ?> more features</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <div class="checkout-pricing">
                            <div class="checkout-pricing-row">
                                <span>Billing Period</span>
                                <span><?php echo $plan['billing_period_months']; ?> <?php echo $plan['billing_period_months'] == 1 ? 'month' : 'months'; ?></span>
                            </div>
                            <div class="checkout-pricing-total">
                                <span class="checkout-total-label">Total Amount</span>
                                <span class="checkout-total-price">
                                    <?php 
                                    $effectivePrice = !empty($plan['discounted_price']) && $plan['discounted_price'] > 0 
                                        ? $plan['discounted_price'] 
                                        : $plan['mrp'];
                                    echo format_price($effectivePrice); 
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="checkout-security-badge">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span>Secure SSL encrypted payment</span>
                        </div>
                    </div>
                </aside>

                <!-- Checkout Form -->
                <div class="checkout-main">
                    <div class="checkout-form-card">
                        <form method="POST" action="<?php echo get_base_url(); ?>/process-payment.php" id="checkout-form" class="checkout-form">
                            <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plan['id']); ?>">
                            
                            <!-- Section 1: Customer Information -->
                            <div class="checkout-form-section">
                                <div class="checkout-section-header">
                                    <span class="checkout-section-num">1</span>
                                    <h3 class="checkout-section-title">Customer Information</h3>
                                </div>
                                
                                <div class="checkout-form-grid">
                                    <div class="checkout-form-group">
                                        <label for="name" class="checkout-label">Full Name <span class="required">*</span></label>
                                        <input 
                                            type="text" 
                                            id="name" 
                                            name="name" 
                                            class="checkout-input" 
                                            value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>"
                                            required
                                            placeholder="Enter your full name"
                                            aria-required="true"
                                        >
                                    </div>
                                    
                                    <div class="checkout-form-group">
                                        <label for="email" class="checkout-label">Email Address <span class="required">*</span></label>
                                        <input 
                                            type="email" 
                                            id="email" 
                                            name="email" 
                                            class="checkout-input checkout-input-readonly" 
                                            value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>"
                                            required
                                            readonly
                                            aria-describedby="email-help"
                                        >
                                        <span id="email-help" class="checkout-help">Your account email (cannot be changed)</span>
                                    </div>
                                </div>
                                
                                <div class="checkout-form-grid">
                                    <div class="checkout-form-group">
                                        <label for="phone-input" class="checkout-label">Phone Number <span class="required">*</span></label>
                                        <?php echo render_phone_input([
                                            'id' => 'phone',
                                            'name' => 'phone',
                                            'value' => $currentUser['phone'] ?? '',
                                            'required' => true,
                                        ]); ?>
                                    </div>
                                    
                                    <div class="checkout-form-group">
                                        <label for="business_name" class="checkout-label">Business Name</label>
                                        <input 
                                            type="text" 
                                            id="business_name" 
                                            name="business_name" 
                                            class="checkout-input" 
                                            value="<?php echo htmlspecialchars($currentUser['business_name'] ?? ''); ?>"
                                            placeholder="Optional"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Billing Address -->
                            <div class="checkout-form-section">
                                <div class="checkout-section-header">
                                    <span class="checkout-section-num">2</span>
                                    <h3 class="checkout-section-title">Billing Address</h3>
                                </div>
                                
                                <div class="checkout-form-grid">
                                    <div class="checkout-form-group">
                                        <label for="billing_full_name" class="checkout-label">Full Name <span class="required">*</span></label>
                                        <input 
                                            type="text" 
                                            id="billing_full_name" 
                                            name="billing_full_name" 
                                            class="checkout-input" 
                                            value="<?php echo htmlspecialchars($billingAddress['full_name'] ?? $currentUser['name'] ?? ''); ?>"
                                            required
                                            placeholder="Full name for billing"
                                        >
                                    </div>
                                    
                                    <div class="checkout-form-group">
                                        <label for="billing_phone" class="checkout-label">Phone Number <span class="required">*</span></label>
                                        <input 
                                            type="tel" 
                                            id="billing_phone" 
                                            name="billing_phone" 
                                            class="checkout-input" 
                                            value="<?php echo htmlspecialchars($billingAddress['phone'] ?? $currentUser['phone'] ?? ''); ?>"
                                            required
                                            placeholder="Contact number"
                                        >
                                    </div>
                                </div>
                                
                                <div class="checkout-form-grid">
                                    <div class="checkout-form-group">
                                        <label for="billing_business_name" class="checkout-label">Business Name</label>
                                        <input 
                                            type="text" 
                                            id="billing_business_name" 
                                            name="billing_business_name" 
                                            class="checkout-input" 
                                            value="<?php echo htmlspecialchars($billingAddress['business_name'] ?? $currentUser['business_name'] ?? ''); ?>"
                                            placeholder="Optional"
                                        >
                                    </div>
                                    
                                    <div class="checkout-form-group">
                                        <label for="billing_business_tax_id" class="checkout-label">Business Tax ID / GSTIN</label>
                                        <input 
                                            type="text" 
                                            id="billing_business_tax_id" 
                                            name="billing_business_tax_id" 
                                            class="checkout-input" 
                                            value="<?php echo htmlspecialchars($billingAddress['business_tax_id'] ?? ''); ?>"
                                            placeholder="Optional (e.g., GSTIN)"
                                        >
                                    </div>
                                </div>
                                
                                <div class="checkout-form-group">
                                    <label for="billing_address_line1" class="checkout-label">Address Line 1 <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        id="billing_address_line1" 
                                        name="billing_address_line1" 
                                        class="checkout-input" 
                                        value="<?php echo htmlspecialchars($billingAddress['address_line1'] ?? ''); ?>"
                                        required
                                        placeholder="Street address, P.O. box"
                                    >
                                </div>
                                
                                <div class="checkout-form-group">
                                    <label for="billing_address_line2" class="checkout-label">Address Line 2</label>
                                    <input 
                                        type="text" 
                                        id="billing_address_line2" 
                                        name="billing_address_line2" 
                                        class="checkout-input" 
                                        value="<?php echo htmlspecialchars($billingAddress['address_line2'] ?? ''); ?>"
                                        placeholder="Apartment, suite, unit, building, floor, etc."
                                    >
                                </div>
                                
                                <div class="checkout-form-grid checkout-form-grid-3">
                                    <div class="checkout-form-group">
                                        <label for="billing_city" class="checkout-label">City <span class="required">*</span></label>
                                        <input 
                                            type="text" 
                                            id="billing_city" 
                                            name="billing_city" 
                                            class="checkout-input" 
                                            value="<?php echo htmlspecialchars($billingAddress['city'] ?? ''); ?>"
                                            required
                                            placeholder="City"
                                        >
                                    </div>
                                    
                                    <div class="checkout-form-group">
                                        <label for="billing_state" class="checkout-label">State <span class="required">*</span></label>
                                        <input 
                                            type="text" 
                                            id="billing_state" 
                                            name="billing_state" 
                                            class="checkout-input" 
                                            value="<?php echo htmlspecialchars($billingAddress['state'] ?? ''); ?>"
                                            required
                                            placeholder="State"
                                        >
                                    </div>
                                    
                                    <div class="checkout-form-group">
                                        <label for="billing_postal_code" class="checkout-label">Postal Code <span class="required">*</span></label>
                                        <input 
                                            type="text" 
                                            id="billing_postal_code" 
                                            name="billing_postal_code" 
                                            class="checkout-input" 
                                            value="<?php echo htmlspecialchars($billingAddress['postal_code'] ?? ''); ?>"
                                            required
                                            placeholder="PIN Code"
                                        >
                                    </div>
                                </div>
                                
                                <div class="checkout-form-group">
                                    <label for="billing_country" class="checkout-label">Country <span class="required">*</span></label>
                                    <input 
                                        type="text" 
                                        id="billing_country" 
                                        name="billing_country" 
                                        class="checkout-input" 
                                        value="<?php echo htmlspecialchars($billingAddress['country'] ?? 'India'); ?>"
                                        required
                                        placeholder="Country"
                                    >
                                </div>
                            </div>

                            <!-- Section 3: Payment Method -->
                            <div class="checkout-form-section">
                                <div class="checkout-section-header">
                                    <span class="checkout-section-num">3</span>
                                    <h3 class="checkout-section-title">Payment Method</h3>
                                </div>
                                
                                <div class="checkout-payment-notice">
                                    <svg class="checkout-notice-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div class="checkout-notice-text">
                                        <strong>Secure Payment Gateway</strong>
                                        <p>You will be redirected to Razorpay secure payment gateway to complete your purchase safely.</p>
                                    </div>
                                </div>
                                
                                <div class="checkout-payment-methods">
                                    <label class="checkout-payment-option">
                                        <input type="radio" name="payment_method" value="razorpay" checked required class="checkout-payment-radio">
                                        <div class="checkout-payment-card">
                                            <img src="<?php echo str_replace('/public', '', get_base_url()); ?>/assets/images/razorpay-logo.svg" alt="Razorpay" class="checkout-payment-logo">
                                            <div class="checkout-payment-info">
                                                <span class="checkout-payment-name">Razorpay</span>
                                                <span class="checkout-payment-desc">Cards, UPI, NetBanking, Wallets & More</span>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Section 4: Terms and Submit -->
                            <div class="checkout-form-section checkout-form-section-last">
                                <div class="checkout-terms">
                                    <label class="checkout-terms-label">
                                        <input type="checkbox" name="accept_terms" value="1" required class="checkout-terms-checkbox" id="accept_terms">
                                        <span class="checkout-terms-text">
                                            I agree to the <a href="<?php echo get_base_url(); ?>/terms.php" target="_blank" class="checkout-terms-link">Terms of Service</a> 
                                            and <a href="<?php echo get_base_url(); ?>/privacy.php" target="_blank" class="checkout-terms-link">Privacy Policy</a>
                                        </span>
                                    </label>
                                </div>
                                
                                <div class="checkout-actions">
                                    <button type="submit" class="btn btn-primary btn-lg checkout-submit-btn" id="checkout-submit">
                                        <svg class="checkout-btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        <span>Proceed to Secure Payment</span>
                                    </button>
                                    <a href="<?php echo get_base_url(); ?>/pricing.php" class="btn btn-outline btn-lg">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Trust Badges -->
                    <div class="checkout-trust-badges">
                        <div class="checkout-trust-item">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>SSL Encrypted</span>
                        </div>
                        <div class="checkout-trust-item">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>Secure Payment</span>
                        </div>
                        <div class="checkout-trust-item">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Money-Back Guarantee</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($plan && $portAvailable): ?>
<script>
(function() {
    'use strict';
    
    // Console logging utility for debugging
    const CheckoutDebug = {
        enabled: <?php echo $config['debug'] ? 'true' : 'false'; ?>,
        
        log: function(message, data = null) {
            if (!this.enabled) return;
            console.log('%c[Checkout]%c ' + message, 'color: #667eea; font-weight: bold;', 'color: inherit;', data || '');
        },
        
        error: function(message, data = null) {
            console.error('%c[Checkout Error]%c ' + message, 'color: #ef4444; font-weight: bold;', 'color: inherit;', data || '');
        },
        
        warn: function(message, data = null) {
            if (!this.enabled) return;
            console.warn('%c[Checkout Warning]%c ' + message, 'color: #f59e0b; font-weight: bold;', 'color: inherit;', data || '');
        },
        
        group: function(title) {
            if (!this.enabled) return;
            console.group('%c[Checkout] ' + title, 'color: #667eea; font-weight: bold;');
        },
        
        groupEnd: function() {
            if (!this.enabled) return;
            console.groupEnd();
        }
    };
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        CheckoutDebug.group('Checkout Page Initialized');
        CheckoutDebug.log('Page loaded successfully');
        CheckoutDebug.log('Plan ID:', '<?php echo htmlspecialchars($plan['id']); ?>');
        CheckoutDebug.log('Plan Name:', '<?php echo htmlspecialchars($plan['name']); ?>');
        CheckoutDebug.log('Plan MRP:', '<?php echo format_price($plan['mrp']); ?>');
        <?php if (!empty($plan['discounted_price'])): ?>
        CheckoutDebug.log('Discounted Price:', '<?php echo format_price($plan['discounted_price']); ?>');
        <?php endif; ?>
        CheckoutDebug.groupEnd();
        
        // Debug: Log payment debug info from session
        <?php if (isset($_SESSION['payment_debug'])): ?>
        <?php $debugData = $_SESSION['payment_debug']; ?>
        CheckoutDebug.group('Payment Debug Info (from previous attempt)');
        CheckoutDebug.log('Step:', <?php echo json_encode($debugData['step'] ?? 'unknown'); ?>);
        CheckoutDebug.log('Key ID Present:', <?php echo json_encode($debugData['key_id_present'] ?? false); ?>);
        CheckoutDebug.log('Key ID Prefix:', <?php echo json_encode($debugData['key_id_prefix'] ?? 'N/A'); ?>);
        CheckoutDebug.log('Order Data:', <?php echo json_encode($debugData['order_data'] ?? null); ?>);
        
        <?php if (isset($debugData['payment_order_result'])): ?>
        CheckoutDebug.log('Payment Order Result:', <?php echo json_encode($debugData['payment_order_result']); ?>);
        <?php if (isset($debugData['payment_order_result']['error'])): ?>
        CheckoutDebug.error('PAYMENT ERROR:', <?php echo json_encode($debugData['payment_order_result']['error']); ?>);
        <?php endif; ?>
        <?php endif; ?>
        
        <?php if (isset($debugData['init_error'])): ?>
        CheckoutDebug.error('INIT ERROR:', <?php echo json_encode($debugData['init_error']); ?>);
        <?php endif; ?>
        
        CheckoutDebug.groupEnd();
        <?php unset($_SESSION['payment_debug']); ?>
        <?php endif; ?>

        // Form handling
        const checkoutForm = document.getElementById('checkout-form');
        const submitBtn = document.getElementById('checkout-submit');
        const termsCheckbox = document.getElementById('accept_terms');
        
        if (!checkoutForm) {
            CheckoutDebug.error('Checkout form not found!');
            return;
        }
        
        CheckoutDebug.log('Form elements found:', {
            form: !!checkoutForm,
            submitBtn: !!submitBtn,
            termsCheckbox: !!termsCheckbox
        });
        
        // Form validation and submission
        checkoutForm.addEventListener('submit', function(e) {
            CheckoutDebug.group('Form Submission');
            CheckoutDebug.log('Form submit triggered');
            
            // Check terms acceptance
            if (!termsCheckbox || !termsCheckbox.checked) {
                e.preventDefault();
                CheckoutDebug.warn('Terms not accepted');
                alert('Please accept the Terms of Service and Privacy Policy to continue.');
                if (termsCheckbox) {
                    termsCheckbox.focus();
                    termsCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                CheckoutDebug.groupEnd();
                return false;
            }
            
            // Validate required fields
            const requiredFields = checkoutForm.querySelectorAll('[required]');
            let allValid = true;
            let firstInvalidField = null;
            const invalidFields = [];
            
            requiredFields.forEach(function(field) {
                let isValid = true;
                
                if (field.type === 'checkbox') {
                    isValid = field.checked;
                } else {
                    isValid = field.value && field.value.trim() !== '';
                }
                
                if (!isValid) {
                    allValid = false;
                    invalidFields.push(field.name || field.id);
                    if (!firstInvalidField) firstInvalidField = field;
                }
            });
            
            if (!allValid) {
                e.preventDefault();
                CheckoutDebug.warn('Validation failed. Invalid fields:', invalidFields);
                alert('Please fill in all required fields.');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                    firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                CheckoutDebug.groupEnd();
                return false;
            }
            
            CheckoutDebug.log('Validation passed, submitting form...');
            
            // Collect form data for logging
            const formData = new FormData(checkoutForm);
            const formDataObj = {};
            formData.forEach(function(value, key) {
                if (key !== 'plan_id') { // Don't log sensitive data
                    formDataObj[key] = value;
                }
            });
            CheckoutDebug.log('Form data:', formDataObj);
            
            // Show loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('checkout-btn-loading');
                submitBtn.innerHTML = '<span class="checkout-spinner"></span><span>Processing...</span>';
                CheckoutDebug.log('Loading state activated');
                
                // Re-enable button after 15 seconds as fallback
                setTimeout(function() {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('checkout-btn-loading');
                        submitBtn.innerHTML = '<svg class="checkout-btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg><span>Proceed to Secure Payment</span>';
                        CheckoutDebug.warn('Submit timeout - button re-enabled');
                    }
                }, 15000);
            }
            
            CheckoutDebug.groupEnd();
        });
        
        // Input validation feedback
        const inputs = checkoutForm.querySelectorAll('.checkout-input');
        inputs.forEach(function(input) {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.classList.add('checkout-input-error');
                    CheckoutDebug.log('Field validation failed:', this.name || this.id);
                } else {
                    this.classList.remove('checkout-input-error');
                }
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('checkout-input-error') && this.value.trim()) {
                    this.classList.remove('checkout-input-error');
                }
            });
        });
        
        CheckoutDebug.log('Event listeners attached');
    });
})();
</script>
<?php endif; ?>

<style>
/* Checkout Page Styles - Matching Website Theme */

/* Hero Section */
.checkout-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: var(--spacing-16) 0;
    position: relative;
    overflow: hidden;
}

.checkout-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.03) 10px, rgba(255,255,255,0.03) 20px);
    pointer-events: none;
}

.checkout-hero-content {
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.checkout-hero-label {
    display: inline-block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: rgba(255,255,255,0.9);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: var(--spacing-3);
    padding: var(--spacing-2) var(--spacing-4);
    background: rgba(255,255,255,0.15);
    border-radius: var(--radius-full);
}

.checkout-hero-title {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-white);
    margin: 0 0 var(--spacing-4) 0;
    line-height: 1.2;
}

.checkout-hero-subtitle {
    font-size: var(--font-size-lg);
    color: rgba(255,255,255,0.9);
    margin: 0;
    line-height: 1.6;
}

/* Content Section */
.checkout-content-section {
    padding: var(--spacing-16) 0;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 60vh;
}

/* Alert Styles */
.checkout-alert {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-3);
    padding: var(--spacing-4);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-6);
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
}

.checkout-alert-danger {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

.checkout-alert-icon {
    flex-shrink: 0;
    margin-top: 2px;
}

.checkout-alert-content {
    flex: 1;
    font-size: var(--font-size-base);
    line-height: 1.5;
}

/* Checkout Grid */
.checkout-grid {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: var(--spacing-8);
    max-width: 1100px;
    margin: 0 auto;
    align-items: start;
}

/* Sidebar - Order Summary */
.checkout-sidebar {
    position: sticky;
    top: var(--spacing-6);
}

.checkout-summary-card {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-6);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 2px solid rgba(102,126,234,0.15);
}

.checkout-summary-header {
    padding-bottom: var(--spacing-4);
    border-bottom: 2px solid var(--color-gray-100);
    margin-bottom: var(--spacing-5);
}

.checkout-summary-title {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0;
}

.checkout-plan-info {
    margin-bottom: var(--spacing-5);
}

.checkout-plan-badge {
    display: inline-block;
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    color: #5b21b6;
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
    padding: 4px 12px;
    border-radius: var(--radius-full);
    margin-bottom: var(--spacing-3);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.checkout-plan-name {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-2) 0;
    line-height: 1.2;
}

.checkout-plan-desc {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0;
    line-height: 1.6;
}

/* Features List */
.checkout-features {
    background: var(--color-gray-50);
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    margin-bottom: var(--spacing-5);
}

.checkout-features-title {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin: 0 0 var(--spacing-3) 0;
}

.checkout-features-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.checkout-feature-item {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-2);
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-2);
    line-height: 1.5;
}

.checkout-feature-item:last-child {
    margin-bottom: 0;
}

.checkout-feature-check {
    color: #10b981;
    flex-shrink: 0;
    margin-top: 2px;
}

.checkout-feature-more {
    font-size: var(--font-size-xs);
    color: var(--color-gray-500);
    font-style: italic;
    margin-top: var(--spacing-2);
}

/* Pricing */
.checkout-pricing {
    border-top: 2px solid var(--color-gray-100);
    padding-top: var(--spacing-5);
}

.checkout-pricing-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin-bottom: var(--spacing-3);
}

.checkout-pricing-row span:last-child {
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
}

.checkout-pricing-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--spacing-4);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: var(--radius-lg);
    margin-top: var(--spacing-4);
}

.checkout-total-label {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-white);
}

.checkout-total-price {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-white);
}

/* Security Badge */
.checkout-security-badge {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
    margin-top: var(--spacing-5);
    padding: var(--spacing-3);
    background: #ecfdf5;
    border-radius: var(--radius-md);
    border: 1px solid #a7f3d0;
}

.checkout-security-badge svg {
    color: #10b981;
    flex-shrink: 0;
}

.checkout-security-badge span {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: #065f46;
}

/* Main Form Area */
.checkout-main {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.checkout-form-card {
    background: var(--color-white);
    border-radius: var(--radius-xl);
    padding: var(--spacing-8);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid var(--color-gray-100);
}

.checkout-form {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-8);
}

/* Form Sections */
.checkout-form-section {
    position: relative;
}

.checkout-form-section-last {
    padding-top: var(--spacing-6);
    border-top: 2px solid var(--color-gray-100);
}

.checkout-section-header {
    display: flex;
    align-items: center;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-5);
    padding-bottom: var(--spacing-3);
    border-bottom: 2px solid var(--color-gray-100);
}

.checkout-section-num {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: var(--color-white);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-bold);
    border-radius: 50%;
    flex-shrink: 0;
}

.checkout-section-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0;
}

/* Form Grid */
.checkout-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.checkout-form-grid:last-child {
    margin-bottom: 0;
}

.checkout-form-group {
    display: flex;
    flex-direction: column;
}

.checkout-label {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-2);
}

.checkout-label .required {
    color: #ef4444;
}

.checkout-input {
    width: 100%;
    padding: var(--spacing-3) var(--spacing-4);
    font-size: var(--font-size-base);
    border: 2px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    transition: all 0.2s ease;
    background: var(--color-white);
    color: var(--color-gray-900);
}

.checkout-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
}

.checkout-input-readonly {
    background: var(--color-gray-50);
    color: var(--color-gray-600);
    cursor: not-allowed;
}

.checkout-input-error {
    border-color: #ef4444;
    background: #fef2f2;
}

.checkout-input-error:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
}

.checkout-help {
    font-size: var(--font-size-xs);
    color: var(--color-gray-500);
    margin-top: var(--spacing-1);
}

/* Payment Notice */
.checkout-payment-notice {
    display: flex;
    gap: var(--spacing-3);
    padding: var(--spacing-4);
    background: #cffafe;
    border: 1px solid #67e8f9;
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-5);
}

.checkout-notice-icon {
    color: #0e7490;
    flex-shrink: 0;
}

.checkout-notice-text {
    flex: 1;
}

.checkout-notice-text strong {
    display: block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: #0e7490;
    margin-bottom: var(--spacing-1);
}

.checkout-notice-text p {
    font-size: var(--font-size-sm);
    color: #164e63;
    margin: 0;
    line-height: 1.5;
}

/* Payment Methods */
.checkout-payment-methods {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-3);
}

.checkout-payment-option {
    display: block;
    cursor: pointer;
}

.checkout-payment-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.checkout-payment-card {
    display: flex;
    align-items: center;
    gap: var(--spacing-4);
    padding: var(--spacing-4);
    background: var(--color-white);
    border: 2px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    transition: all 0.2s ease;
}

.checkout-payment-radio:checked + .checkout-payment-card {
    border-color: #667eea;
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
}

.checkout-payment-logo {
    width: auto;
    height: 24px;
    object-fit: contain;
    flex-shrink: 0;
}

.checkout-payment-icon {
    color: #667eea;
    flex-shrink: 0;
}

.checkout-payment-info {
    display: flex;
    flex-direction: column;
}

.checkout-payment-name {
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
}

.checkout-payment-desc {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
}

/* Terms */
.checkout-terms {
    padding: var(--spacing-4);
    background: var(--color-gray-50);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-6);
}

.checkout-terms-label {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-3);
    cursor: pointer;
}

.checkout-terms-checkbox {
    width: 20px;
    height: 20px;
    margin-top: 2px;
    cursor: pointer;
    flex-shrink: 0;
    accent-color: #667eea;
}

.checkout-terms-text {
    font-size: var(--font-size-sm);
    color: var(--color-gray-700);
    line-height: 1.6;
}

.checkout-terms-link {
    color: #667eea;
    text-decoration: underline;
    font-weight: var(--font-weight-medium);
    transition: color 0.2s ease;
}

.checkout-terms-link:hover {
    color: #5b21b6;
}

/* Actions */
.checkout-actions {
    display: flex;
    gap: var(--spacing-4);
}

.checkout-submit-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    transition: all 0.3s ease;
}

.checkout-submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102,126,234,0.4);
}

.checkout-submit-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.checkout-btn-icon {
    flex-shrink: 0;
}

.checkout-btn-loading {
    pointer-events: none;
}

/* Spinner */
.checkout-spinner {
    display: inline-block;
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: var(--color-white);
    border-radius: 50%;
    animation: checkoutSpin 0.8s linear infinite;
    margin-right: var(--spacing-2);
}

@keyframes checkoutSpin {
    to { transform: rotate(360deg); }
}

/* Trust Badges */
.checkout-trust-badges {
    display: flex;
    justify-content: center;
    gap: var(--spacing-8);
    padding: var(--spacing-5);
    background: var(--color-white);
    border-radius: var(--radius-lg);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid var(--color-gray-100);
}

.checkout-trust-item {
    display: flex;
    align-items: center;
    gap: var(--spacing-2);
}

.checkout-trust-item svg {
    color: #10b981;
    flex-shrink: 0;
}

.checkout-trust-item span {
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    color: var(--color-gray-700);
}

/* Unavailable State */
.checkout-unavailable-card {
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
    padding: var(--spacing-12) var(--spacing-8);
    background: var(--color-white);
    border-radius: var(--radius-xl);
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 2px solid #fef3c7;
}

.checkout-unavailable-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto var(--spacing-6);
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.checkout-unavailable-icon svg {
    color: #d97706;
}

.checkout-unavailable-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin: 0 0 var(--spacing-4) 0;
}

.checkout-unavailable-text {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.7;
    margin: 0 0 var(--spacing-4) 0;
}

.checkout-unavailable-contact {
    font-size: var(--font-size-sm);
    color: var(--color-gray-600);
    margin: 0 0 var(--spacing-6) 0;
}

.checkout-unavailable-contact a {
    color: #667eea;
    text-decoration: underline;
    font-weight: var(--font-weight-medium);
}

.checkout-unavailable-actions {
    display: flex;
    gap: var(--spacing-3);
    justify-content: center;
    flex-wrap: wrap;
}

/* Responsive Styles */
@media (max-width: 1024px) {
    .checkout-grid {
        grid-template-columns: 1fr;
        max-width: 600px;
    }
    
    .checkout-sidebar {
        position: static;
        order: 2;
    }
    
    .checkout-main {
        order: 1;
    }
}

@media (max-width: 768px) {
    .checkout-hero {
        padding: var(--spacing-12) 0;
    }
    
    .checkout-hero-title {
        font-size: var(--font-size-3xl);
    }
    
    .checkout-content-section {
        padding: var(--spacing-12) 0;
    }
    
    .checkout-form-card {
        padding: var(--spacing-6);
    }
    
    .checkout-form-grid {
        grid-template-columns: 1fr;
    }
    
    .checkout-section-header {
        flex-wrap: wrap;
    }
    
    .checkout-section-num {
        width: 28px;
        height: 28px;
        font-size: var(--font-size-xs);
    }
    
    .checkout-section-title {
        font-size: var(--font-size-base);
    }
    
    .checkout-summary-card {
        padding: var(--spacing-5);
    }
    
    .checkout-plan-name {
        font-size: var(--font-size-xl);
    }
    
    .checkout-total-price {
        font-size: var(--font-size-xl);
    }
    
    .checkout-actions {
        flex-direction: column;
    }
    
    .checkout-trust-badges {
        flex-direction: column;
        gap: var(--spacing-3);
        align-items: center;
    }
    
    .checkout-unavailable-card {
        padding: var(--spacing-8) var(--spacing-4);
    }
    
    .checkout-unavailable-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .checkout-unavailable-actions .btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .checkout-hero-label {
        font-size: var(--font-size-xs);
    }
    
    .checkout-hero-title {
        font-size: var(--font-size-2xl);
    }
    
    .checkout-hero-subtitle {
        font-size: var(--font-size-base);
    }
    
    .checkout-form-card {
        padding: var(--spacing-4);
    }
    
    .checkout-summary-card {
        padding: var(--spacing-4);
    }
    
    .checkout-payment-notice {
        flex-direction: column;
        text-align: center;
    }
    
    .checkout-payment-card {
        flex-direction: column;
        text-align: center;
        gap: var(--spacing-2);
    }
}
</style>

<?php
// Include footer
include_footer();
?>
