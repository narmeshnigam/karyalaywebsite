<?php

/**
 * SellerPortal System
 * Refund Policy Page
 */

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/app.php';

if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/../includes/auth_helpers.php';
startSecureSession();
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Setting;

// Load refund content from settings
$settingModel = new Setting();
$refundContent = $settingModel->get('legal_refund_policy', '');
$lastUpdated = $settingModel->get('legal_refund_updated', date('F d, Y'));

// Default content if not set
if (empty($refundContent)) {
    $refundContent = '<h2>1. Refund Eligibility</h2>
<p>We offer refunds under the following conditions:</p>
<ul>
    <li>Service cancellation within 30 days of initial purchase</li>
    <li>Technical issues that prevent service usage and cannot be resolved</li>
    <li>Billing errors or duplicate charges</li>
</ul>

<h2>2. Refund Request Process</h2>
<p>To request a refund, please follow these steps:</p>
<ol>
    <li>Contact our support team through the support ticket system</li>
    <li>Provide your account details and reason for refund</li>
    <li>Allow 3-5 business days for review</li>
    <li>Receive confirmation and refund processing timeline</li>
</ol>

<h2>3. Refund Timeline</h2>
<p>Once your refund request is approved:</p>
<ul>
    <li>Refunds are processed within 5-7 business days</li>
    <li>The refund will be credited to your original payment method</li>
    <li>Bank processing may take an additional 3-5 business days</li>
</ul>

<h2>4. Non-Refundable Items</h2>
<p>The following are not eligible for refunds:</p>
<ul>
    <li>Services used beyond the 30-day trial period</li>
    <li>Custom development or setup fees</li>
    <li>Third-party service charges</li>
    <li>Subscription renewals (must be cancelled before renewal date)</li>
</ul>

<h2>5. Partial Refunds</h2>
<p>In some cases, we may offer partial refunds based on:</p>
<ul>
    <li>Prorated usage of the service</li>
    <li>Time remaining in the billing cycle</li>
    <li>Specific circumstances reviewed on a case-by-case basis</li>
</ul>

<h2>6. Cancellation Policy</h2>
<p>You may cancel your subscription at any time. Upon cancellation:</p>
<ul>
    <li>Service continues until the end of the current billing period</li>
    <li>No charges will be made for subsequent periods</li>
    <li>Data export is available for 30 days after cancellation</li>
</ul>

<h2>7. Dispute Resolution</h2>
<p>If you disagree with a refund decision, you may:</p>
<ul>
    <li>Request a review by our management team</li>
    <li>Provide additional documentation supporting your claim</li>
    <li>Escalate through our formal dispute process</li>
</ul>

<h2>8. Contact Information</h2>
<p>For refund inquiries, please contact our billing support team through the support ticket system or email us directly.</p>';
}

$page_title = 'Refund Policy';
$page_description = 'Our refund and cancellation policy';

include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="legal-hero">
    <div class="container">
        <div class="legal-hero-content">
            <h1 class="legal-hero-title">Refund Policy</h1>
            <p class="legal-hero-subtitle">
                Understand our refund and cancellation terms
            </p>
            <p class="legal-last-updated">Last Updated: <?php echo htmlspecialchars($lastUpdated); ?></p>
        </div>
    </div>
</section>

<!-- Content Section -->
<section class="legal-content-section">
    <div class="container">
        <div class="legal-content-wrapper">
            <div class="legal-content">
                <?php echo $refundContent; ?>
            </div>
            
            <!-- Sidebar -->
            <aside class="legal-sidebar">
                <div class="legal-sidebar-card">
                    <h3 class="legal-sidebar-title">Legal Pages</h3>
                    <ul class="legal-sidebar-links">
                        <li><a href="<?php echo get_base_url(); ?>/terms.php" class="legal-sidebar-link">Terms of Service</a></li>
                        <li><a href="<?php echo get_base_url(); ?>/privacy.php" class="legal-sidebar-link">Privacy Policy</a></li>
                        <li><a href="<?php echo get_base_url(); ?>/refund.php" class="legal-sidebar-link active">Refund Policy</a></li>
                    </ul>
                </div>
                
                <div class="legal-sidebar-card">
                    <h3 class="legal-sidebar-title">Need Help?</h3>
                    <p class="legal-sidebar-text">Have questions about refunds? Our support team is here to help.</p>
                    <a href="<?php echo get_base_url(); ?>/contact.php" class="btn btn-outline btn-sm btn-block">Contact Us</a>
                </div>
            </aside>
        </div>
    </div>
</section>

<style>
/* Legal Pages Styles */
.legal-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 0 40px;
    text-align: center;
    color: white;
}

.legal-hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.legal-hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 15px;
}

.legal-hero-subtitle {
    font-size: 1.125rem;
    opacity: 0.95;
    margin: 0 0 10px;
}

.legal-last-updated {
    font-size: 0.875rem;
    opacity: 0.85;
    margin: 0;
}

.legal-content-section {
    padding: 60px 0;
    background: #f8f9fa;
}

.legal-content-wrapper {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
}

.legal-content {
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.legal-content h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1a202c;
    margin: 30px 0 15px;
}

.legal-content h2:first-child {
    margin-top: 0;
}

.legal-content h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2d3748;
    margin: 25px 0 12px;
}

.legal-content p {
    font-size: 1rem;
    color: #4a5568;
    line-height: 1.7;
    margin: 0 0 15px;
}

.legal-content ul, .legal-content ol {
    margin: 0 0 15px 20px;
    color: #4a5568;
    line-height: 1.7;
}

.legal-content li {
    margin-bottom: 8px;
}

.legal-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.legal-sidebar-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.legal-sidebar-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 15px;
}

.legal-sidebar-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.legal-sidebar-links li {
    margin-bottom: 8px;
}

.legal-sidebar-link {
    display: block;
    padding: 8px 12px;
    color: #4a5568;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.2s;
}

.legal-sidebar-link:hover {
    background: #f7fafc;
    color: #667eea;
}

.legal-sidebar-link.active {
    background: #667eea;
    color: white;
}

.legal-sidebar-text {
    font-size: 0.9375rem;
    color: #4a5568;
    line-height: 1.6;
    margin: 0 0 15px;
}

@media (max-width: 768px) {
    .legal-hero-title {
        font-size: 2rem;
    }
    
    .legal-content-wrapper {
        grid-template-columns: 1fr;
    }
    
    .legal-content {
        padding: 30px 20px;
    }
    
    .legal-sidebar {
        order: -1;
    }
}
</style>

<?php
include_footer();
?>
