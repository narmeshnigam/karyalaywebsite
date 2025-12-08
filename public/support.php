<?php

/**
 * Karyalay Portal System
 * Support/FAQ Page
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

// Set page variables
$page_title = 'Support & FAQ';
$page_description = 'Find answers to common questions and get help';

// Include header
include_header($page_title, $page_description);
?>

<!-- Page Header -->
<section class="section bg-gray-50">
    <div class="container">
        <h1 class="text-4xl font-bold mb-4">Support & FAQ</h1>
        <p class="text-xl text-gray-600">
            Find answers to common questions or get in touch with our support team
        </p>
    </div>
</section>

<!-- Quick Links -->
<section class="section">
    <div class="container">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <div class="card text-center">
                <div class="card-body">
                    <div class="mb-4">
                        <svg class="w-12 h-12 mx-auto text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Contact Support</h3>
                    <p class="text-gray-600 mb-4">Get help from our support team</p>
                    <a href="/karyalayportal/contact.php" class="btn btn-outline btn-sm">Contact Us</a>
                </div>
            </div>
            
            <div class="card text-center">
                <div class="card-body">
                    <div class="mb-4">
                        <svg class="w-12 h-12 mx-auto text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Documentation</h3>
                    <p class="text-gray-600 mb-4">Browse our comprehensive guides</p>
                    <a href="/karyalayportal/modules.php" class="btn btn-outline btn-sm">View Modules</a>
                </div>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <div class="card text-center">
                    <div class="card-body">
                        <div class="mb-4">
                            <svg class="w-12 h-12 mx-auto text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">Create Ticket</h3>
                        <p class="text-gray-600 mb-4">Submit a support ticket</p>
                        <a href="/app/support/tickets/new" class="btn btn-primary btn-sm">New Ticket</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card text-center">
                    <div class="card-body">
                        <div class="mb-4">
                            <svg class="w-12 h-12 mx-auto text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">Customer Login</h3>
                        <p class="text-gray-600 mb-4">Access your account</p>
                        <a href="/karyalayportal/login.php" class="btn btn-primary btn-sm">Login</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="section bg-gray-50">
    <div class="container">
        <h2 class="text-3xl font-bold mb-8 text-center">Frequently Asked Questions</h2>
        <div class="max-w-4xl mx-auto space-y-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="text-xl font-semibold mb-3">How do I get started with Karyalay?</h3>
                    <p class="text-gray-700">
                        Getting started is easy! Simply choose a plan that fits your needs, create an account, 
                        and you'll receive access to your dedicated instance. Our setup guide will walk you 
                        through the initial configuration.
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3 class="text-xl font-semibold mb-3">What payment methods do you accept?</h3>
                    <p class="text-gray-700">
                        We accept all major credit cards, debit cards, and online payment methods through our 
                        secure payment gateway. All transactions are encrypted and secure.
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3 class="text-xl font-semibold mb-3">Can I upgrade or downgrade my plan?</h3>
                    <p class="text-gray-700">
                        Yes! You can change your plan at any time from your customer portal. Changes will be 
                        reflected in your next billing cycle, and we'll prorate any differences.
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3 class="text-xl font-semibold mb-3">How do I contact support?</h3>
                    <p class="text-gray-700">
                        If you're a customer, you can create a support ticket from your portal. For general 
                        inquiries, use our contact form or email us directly. We typically respond within 24 hours.
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3 class="text-xl font-semibold mb-3">Is my data secure?</h3>
                    <p class="text-gray-700">
                        Absolutely. We use enterprise-grade security measures including encryption, regular backups, 
                        and secure data centers. Your data is protected and always accessible only to you.
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3 class="text-xl font-semibold mb-3">Do you offer training or onboarding?</h3>
                    <p class="text-gray-700">
                        Yes! We provide comprehensive documentation, video tutorials, and can arrange personalized 
                        training sessions for your team. Contact us to learn more about our onboarding options.
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h3 class="text-xl font-semibold mb-3">What happens if I cancel my subscription?</h3>
                    <p class="text-gray-700">
                        You can cancel anytime. Your service will continue until the end of your current billing 
                        period. We can provide an export of your data before your account closes.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="section">
    <div class="container">
        <div class="card" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <div class="card-body">
                <h2 class="text-3xl font-bold mb-4">Still Have Questions?</h2>
                <p class="text-lg text-gray-600 mb-6">
                    Our support team is here to help you
                </p>
                <div class="flex gap-4 justify-center flex-wrap">
                    <a href="/karyalayportal/contact.php" class="btn btn-primary btn-lg">Contact Support</a>
                    <a href="/karyalayportal/demo.php" class="btn btn-outline btn-lg">Request Demo</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_footer();
?>
