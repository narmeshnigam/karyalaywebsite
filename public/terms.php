<?php

/**
 * SellerPortal System
 * Terms of Service Page
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

// Load terms content from settings
$settingModel = new Setting();
$termsContent = $settingModel->get('legal_terms_of_service', '');
$lastUpdated = $settingModel->get('legal_terms_updated', date('F d, Y'));

// Default content if not set
if (empty($termsContent)) {
    $termsContent = '<h2>1. Acceptance of Terms</h2>
<p>By accessing and using the Karyalay platform, you accept and agree to be bound by the terms and provision of this agreement.</p>

<h2>2. Use License</h2>
<p>Permission is granted to temporarily access the materials (information or software) on Karyalay\'s platform for personal, non-commercial transitory viewing only.</p>

<h2>3. Disclaimer</h2>
<p>The materials on Karyalay\'s platform are provided on an \'as is\' basis. Karyalay makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

<h2>4. Limitations</h2>
<p>In no event shall Karyalay or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Karyalay\'s platform.</p>

<h2>5. Accuracy of Materials</h2>
<p>The materials appearing on Karyalay\'s platform could include technical, typographical, or photographic errors. Karyalay does not warrant that any of the materials on its platform are accurate, complete or current.</p>

<h2>6. Links</h2>
<p>Karyalay has not reviewed all of the sites linked to its platform and is not responsible for the contents of any such linked site.</p>

<h2>7. Modifications</h2>
<p>Karyalay may revise these terms of service for its platform at any time without notice. By using this platform you are agreeing to be bound by the then current version of these terms of service.</p>

<h2>8. Governing Law</h2>
<p>These terms and conditions are governed by and construed in accordance with the laws and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>';
}

$page_title = 'Terms of Service';
$page_description = 'Terms and conditions for using Karyalay services';

include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="legal-hero">
    <div class="container">
        <div class="legal-hero-content">
            <h1 class="legal-hero-title">Terms of Service</h1>
            <p class="legal-hero-subtitle">
                Please read these terms carefully before using our services
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
                <?php echo $termsContent; ?>
            </div>
            
            <!-- Sidebar -->
            <aside class="legal-sidebar">
                <div class="legal-sidebar-card">
                    <h3 class="legal-sidebar-title">Legal Pages</h3>
                    <ul class="legal-sidebar-links">
                        <li><a href="<?php echo get_base_url(); ?>/terms.php" class="legal-sidebar-link active">Terms of Service</a></li>
                        <li><a href="<?php echo get_base_url(); ?>/privacy.php" class="legal-sidebar-link">Privacy Policy</a></li>
                        <li><a href="<?php echo get_base_url(); ?>/refund.php" class="legal-sidebar-link">Refund Policy</a></li>
                    </ul>
                </div>
                
                <div class="legal-sidebar-card">
                    <h3 class="legal-sidebar-title">Need Help?</h3>
                    <p class="legal-sidebar-text">If you have questions about our terms, please contact us.</p>
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
