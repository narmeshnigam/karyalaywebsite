<?php

/**
 * Karyalay Portal System
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

// Start secure session
startSecureSession();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: /karyalayportal/login.php');
    exit;
}

// Include template helpers
require_once __DIR__ . '/../includes/template_helpers.php';

// Set page variables
$page_title = 'Payment Successful';
$page_description = 'Your payment was successful';

// Include header
include_header($page_title, $page_description);
?>

<!-- Payment Success Page -->
<section class="section">
    <div class="container">
        <div class="max-w-2xl mx-auto text-center">
            <div class="mb-6">
                <svg class="w-20 h-20 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold mb-4">Payment Successful!</h1>
            
            <p class="text-lg text-gray-600 mb-6">
                Thank you for your purchase. Your subscription is being activated and you will receive a confirmation email shortly.
            </p>
            
            <?php if (isset($_GET['payment_id'])): ?>
                <p class="text-sm text-gray-500 mb-6">
                    Payment ID: <?php echo htmlspecialchars($_GET['payment_id']); ?>
                </p>
            <?php endif; ?>
            
            <div class="alert alert-info mb-6">
                <p>
                    <strong>What's Next?</strong><br>
                    Your Karyalay instance is being provisioned. You will receive an email with setup instructions and your instance URL within the next few minutes.
                </p>
            </div>
            
            <div class="flex gap-4 justify-center">
                <a href="/karyalayportal/app/dashboard.php" class="btn btn-primary btn-lg">
                    Go to Dashboard
                </a>
                <a href="/karyalayportal/index.php" class="btn btn-outline btn-lg">
                    Back to Home
                </a>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_footer();
?>

