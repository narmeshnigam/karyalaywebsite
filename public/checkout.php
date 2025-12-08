<?php

/**
 * Karyalay Portal System
 * Checkout Page
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
use Karyalay\Services\PortAvailabilityService;

$error = null;
$plan = null;
$portAvailable = false;
$availablePortsCount = 0;

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
    
    // Check port availability using service
    $availabilityCheck = $portAvailabilityService->checkAvailability($plan['id']);
    $portAvailable = $availabilityCheck['available'];
    $availablePortsCount = $availabilityCheck['count'];
    
} catch (Exception $e) {
    error_log('Checkout page error: ' . $e->getMessage());
    $error = 'An error occurred. Please try again.';
}

// Get current user
$currentUser = getCurrentUser();

// Set page variables
$page_title = 'Checkout';
$page_description = 'Complete your purchase';
$additional_css = [
    css_url('components.css'),
    css_url('checkout.css')
];

// Include header
include_header($page_title, $page_description);
?>

<!-- Checkout Page -->
<section class="section checkout-hero">
    <div class="container">
        <div class="checkout-header">
            <h1 class="section-title">Complete Your Purchase</h1>
            <p class="section-subtitle">You're just one step away from transforming your business</p>
        </div>
    </div>
</section>

<section class="section checkout-content">
    <div class="container">
        <div class="checkout-wrapper">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$portAvailable): ?>
                <div class="checkout-unavailable">
                    <div class="unavailable-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h2 class="unavailable-title">Port Unavailable</h2>
                    <p class="unavailable-text">
                        Unfortunately, there are no available ports for this plan at the moment. 
                        Our team has been notified and will provision additional capacity soon.
                    </p>
                    <p class="unavailable-contact">
                        Please contact us at <a href="mailto:<?php echo htmlspecialchars($config['admin_email'] ?? 'admin@karyalay.com'); ?>">
                            <?php echo htmlspecialchars($config['admin_email'] ?? 'admin@karyalay.com'); ?>
                        </a> for more information.
                    </p>
                    <div class="unavailable-actions">
                        <a href="/karyalayportal/pricing.php" class="btn btn-primary">Back to Pricing</a>
                        <a href="/karyalayportal/contact.php" class="btn btn-outline">Contact Us</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($plan && $portAvailable): ?>
                <div class="checkout-grid">
                    <!-- Order Summary -->
                    <aside class="checkout-sidebar">
                        <div class="order-summary-card">
                            <h2 class="order-summary-title">Order Summary</h2>
                            
                            <div class="order-plan">
                                <div class="order-plan-badge">Selected Plan</div>
                                <h3 class="order-plan-name">
                                    <?php echo htmlspecialchars($plan['name']); ?>
                                </h3>
                                <?php if (!empty($plan['description'])): ?>
                                    <p class="order-plan-description">
                                        <?php echo htmlspecialchars($plan['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($plan['features']) && is_array($plan['features'])): ?>
                                <div class="order-features">
                                    <p class="order-features-title">What's Included:</p>
                                    <ul class="order-features-list">
                                        <?php foreach (array_slice($plan['features'], 0, 5) as $feature): ?>
                                            <li class="order-feature-item">
                                                <svg class="order-feature-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <span><?php echo htmlspecialchars($feature); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (count($plan['features']) > 5): ?>
                                            <li class="order-feature-more">
                                                + <?php echo count($plan['features']) - 5; ?> more features
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <div class="order-pricing">
                                <div class="order-pricing-row">
                                    <span class="order-pricing-label">Billing Period:</span>
                                    <span class="order-pricing-value">
                                        <?php echo $plan['billing_period_months']; ?> 
                                        <?php echo $plan['billing_period_months'] == 1 ? 'month' : 'months'; ?>
                                    </span>
                                </div>
                                <div class="order-pricing-total">
                                    <span class="order-total-label">Total Amount:</span>
                                    <span class="order-total-price">
                                        <?php echo htmlspecialchars($plan['currency']); ?> 
                                        <?php echo number_format($plan['price'], 2); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="order-security">
                                <svg class="order-security-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <span class="order-security-text">Secure SSL encrypted payment</span>
                            </div>
                        </div>
                    </aside>
                    
                    <!-- Billing Information Form -->
                    <div class="checkout-main">
                        <div class="checkout-form-card">
                            <form method="POST" action="/karyalayportal/process-payment.php" id="checkout-form" class="checkout-form">
                                <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plan['id']); ?>">
                                
                                <!-- Customer Information -->
                                <div class="form-section">
                                    <h3 class="form-section-title">
                                        <span class="form-section-number">1</span>
                                        Customer Information
                                    </h3>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="name" class="form-label">Full Name *</label>
                                            <input 
                                                type="text" 
                                                id="name" 
                                                name="name" 
                                                class="form-input" 
                                                value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>"
                                                required
                                                placeholder="Enter your full name"
                                            >
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email Address *</label>
                                            <input 
                                                type="email" 
                                                id="email" 
                                                name="email" 
                                                class="form-input" 
                                                value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>"
                                                required
                                                readonly
                                            >
                                            <span class="form-help">Your account email (cannot be changed)</span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="phone" class="form-label">Phone Number *</label>
                                            <input 
                                                type="tel" 
                                                id="phone" 
                                                name="phone" 
                                                class="form-input" 
                                                value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>"
                                                required
                                                placeholder="+1 (555) 000-0000"
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
                                                placeholder="Optional"
                                            >
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Payment Method -->
                                <div class="form-section">
                                    <h3 class="form-section-title">
                                        <span class="form-section-number">2</span>
                                        Payment Method
                                    </h3>
                                    
                                    <div class="payment-notice">
                                        <svg class="payment-notice-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div class="payment-notice-content">
                                            <strong>Secure Payment Gateway</strong>
                                            <p>You will be redirected to our secure payment gateway to complete your purchase safely.</p>
                                        </div>
                                    </div>
                                    
                                    <div class="payment-methods">
                                        <label class="payment-method-option">
                                            <input 
                                                type="radio" 
                                                name="payment_method" 
                                                value="card" 
                                                checked
                                                required
                                                class="payment-method-radio"
                                            >
                                            <div class="payment-method-content">
                                                <svg class="payment-method-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                </svg>
                                                <div>
                                                    <span class="payment-method-name">Credit/Debit Card</span>
                                                    <span class="payment-method-desc">Visa, Mastercard, Amex</span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Terms and Conditions -->
                                <div class="form-section">
                                    <div class="terms-agreement">
                                        <label class="terms-checkbox-label">
                                            <input 
                                                type="checkbox" 
                                                name="accept_terms" 
                                                value="1"
                                                required
                                                class="terms-checkbox"
                                                id="accept_terms"
                                            >
                                            <span class="terms-text">
                                                I agree to the <a href="/karyalayportal/terms.php" target="_blank" class="terms-link">Terms of Service</a> 
                                                and <a href="/karyalayportal/privacy.php" target="_blank" class="terms-link">Privacy Policy</a>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Submit Button -->
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-lg checkout-submit">
                                        <svg class="btn-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        Proceed to Secure Payment
                                    </button>
                                    <a href="/karyalayportal/pricing.php" class="btn btn-outline btn-lg">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Trust Badges -->
                        <div class="checkout-trust">
                            <div class="trust-badge">
                                <svg class="trust-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                <span>SSL Encrypted</span>
                            </div>
                            <div class="trust-badge">
                                <svg class="trust-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>Secure Payment</span>
                            </div>
                            <div class="trust-badge">
                                <svg class="trust-icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Money-Back Guarantee</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($plan && $portAvailable): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkout-form');
    
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            // Check if terms are accepted
            const termsCheckbox = document.getElementById('accept_terms');
            
            if (!termsCheckbox || !termsCheckbox.checked) {
                e.preventDefault();
                alert('Please accept the Terms of Service and Privacy Policy to continue.');
                if (termsCheckbox) {
                    termsCheckbox.focus();
                    termsCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            
            // Validate all required fields
            const requiredFields = checkoutForm.querySelectorAll('[required]');
            let allValid = true;
            let firstInvalidField = null;
            
            requiredFields.forEach(field => {
                if (field.type === 'checkbox') {
                    if (!field.checked) {
                        allValid = false;
                        if (!firstInvalidField) firstInvalidField = field;
                    }
                } else {
                    if (!field.value || field.value.trim() === '') {
                        allValid = false;
                        if (!firstInvalidField) firstInvalidField = field;
                    }
                }
            });
            
            if (!allValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                    firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }
            
            // Show loading state
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalHTML = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
                
                // Re-enable button after 10 seconds as a fallback
                setTimeout(function() {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHTML;
                    }
                }, 10000);
            }
        });
    }
});
</script>
<?php endif; ?>

<?php
// Include footer
include_footer();
?>
