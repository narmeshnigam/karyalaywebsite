<?php

/**
 * SellerPortal System
 * Payment Cancelled Page
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

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ' . get_base_url() . '/login.php');
    exit;
}

// Include template helpers
require_once __DIR__ . '/../includes/template_helpers.php';

// Set page variables
$page_title = 'Payment Cancelled';
$page_description = 'Payment was cancelled';

// Include header
include_header($page_title, $page_description);
?>

<!-- Payment Cancelled Page -->
<section class="section">
    <div class="container">
        <div class="max-w-2xl mx-auto text-center">
            <div class="mb-6">
                <svg class="w-20 h-20 text-yellow-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold mb-4">Payment Cancelled</h1>
            
            <p class="text-lg text-gray-600 mb-6">
                You have cancelled the payment process. No charges have been made to your account.
            </p>
            
            <div class="alert alert-info mb-6">
                <p>
                    Your selected plan is still available. You can complete your purchase anytime you're ready.
                </p>
            </div>
            
            <div class="flex gap-4 justify-center">
                <a href="<?php echo get_base_url(); ?>/checkout.php" class="btn btn-primary btn-lg">
                    Complete Purchase
                </a>
                <a href="<?php echo get_base_url(); ?>/pricing.php" class="btn btn-outline btn-lg">
                    View Plans
                </a>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_footer();
?>

