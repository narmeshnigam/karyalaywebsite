<?php

/**
 * SellerPortal System
 * Privacy Policy Page
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

// Load privacy content from settings
$settingModel = new Setting();
$privacyContent = $settingModel->get('legal_privacy_policy', '');
$lastUpdated = $settingModel->get('legal_privacy_updated', date('F d, Y'));

// Default content if not set
if (empty($privacyContent)) {
    $privacyContent = '<h2>1. Information We Collect</h2>
<p>We collect information that you provide directly to us, including when you create an account, subscribe to our services, or contact us for support.</p>

<h3>Personal Information</h3>
<ul>
    <li>Name and contact information</li>
    <li>Email address and phone number</li>
    <li>Billing and payment information</li>
    <li>Company information</li>
</ul>

<h2>2. How We Use Your Information</h2>
<p>We use the information we collect to:</p>
<ul>
    <li>Provide, maintain, and improve our services</li>
    <li>Process transactions and send related information</li>
    <li>Send technical notices and support messages</li>
    <li>Respond to your comments and questions</li>
    <li>Monitor and analyze trends and usage</li>
</ul>

<h2>3. Information Sharing</h2>
<p>We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
<ul>
    <li>With your consent</li>
    <li>To comply with legal obligations</li>
    <li>To protect our rights and prevent fraud</li>
    <li>With service providers who assist in our operations</li>
</ul>

<h2>4. Data Security</h2>
<p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

<h2>5. Data Retention</h2>
<p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this privacy policy, unless a longer retention period is required by law.</p>

<h2>6. Your Rights</h2>
<p>You have the right to:</p>
<ul>
    <li>Access your personal information</li>
    <li>Correct inaccurate data</li>
    <li>Request deletion of your data</li>
    <li>Object to processing of your data</li>
    <li>Export your data</li>
</ul>

<h2>7. Cookies</h2>
<p>We use cookies and similar tracking technologies to track activity on our service and hold certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.</p>

<h2>8. Changes to This Policy</h2>
<p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date.</p>

<h2>9. Contact Us</h2>
<p>If you have any questions about this Privacy Policy, please contact us through our contact page.</p>';
}

$page_title = 'Privacy Policy';
$page_description = 'How we collect, use, and protect your personal information';

include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="legal-hero">
    <div class="container">
        <div class="legal-hero-content">
            <h1 class="legal-hero-title">Privacy Policy</h1>
            <p class="legal-hero-subtitle">
                Your privacy is important to us. Learn how we protect your data.
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
                <?php echo $privacyContent; ?>
            </div>
            
            <!-- Sidebar -->
            <aside class="legal-sidebar">
                <div class="legal-sidebar-card">
                    <h3 class="legal-sidebar-title">Legal Pages</h3>
                    <ul class="legal-sidebar-links">
                        <li><a href="<?php echo get_base_url(); ?>/terms.php" class="legal-sidebar-link">Terms of Service</a></li>
                        <li><a href="<?php echo get_base_url(); ?>/privacy.php" class="legal-sidebar-link active">Privacy Policy</a></li>
                        <li><a href="<?php echo get_base_url(); ?>/refund.php" class="legal-sidebar-link">Refund Policy</a></li>
                    </ul>
                </div>
                
                <div class="legal-sidebar-card">
                    <h3 class="legal-sidebar-title">Need Help?</h3>
                    <p class="legal-sidebar-text">If you have questions about our privacy practices, please contact us.</p>
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
