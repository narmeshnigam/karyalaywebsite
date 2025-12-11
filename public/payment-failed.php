<?php

/**
 * SellerPortal System
 * Payment Failed Page
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

// Get error message if provided
$errorMessage = $_GET['error'] ?? 'Payment processing failed';

// Set page variables
$page_title = 'Payment Failed';
$page_description = 'Payment processing failed';

// Include header
include_header($page_title, $page_description);
?>

<!-- Payment Failed Page -->
<section class="section">
    <div class="container">
        <div class="max-w-2xl mx-auto text-center">
            <div class="mb-6">
                <svg class="w-20 h-20 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold mb-4">Payment Failed</h1>
            
            <p class="text-lg text-gray-600 mb-6">
                Unfortunately, your payment could not be processed.
            </p>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error mb-6">
                    <p><?php echo htmlspecialchars($errorMessage); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info mb-6">
                <p>
                    <strong>What can you do?</strong><br>
                    • Check your payment details and try again<br>
                    • Try a different payment method<br>
                    • Contact your bank if the issue persists<br>
                    • Reach out to our support team for assistance
                </p>
            </div>
            
            <div class="flex gap-4 justify-center">
                <a href="<?php echo get_base_url(); ?>/checkout.php" class="btn btn-primary btn-lg">
                    Try Again
                </a>
                <a href="<?php echo get_base_url(); ?>/contact.php" class="btn btn-outline btn-lg">
                    Contact Support
                </a>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_footer();
?>

