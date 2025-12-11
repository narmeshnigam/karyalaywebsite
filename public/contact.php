<?php

/**
 * SellerPortal System
 * Contact Page
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

use Karyalay\Models\Lead;
use Karyalay\Models\Setting;
use Karyalay\Services\CsrfService;
use Karyalay\Services\EmailService;

$csrfService = new CsrfService();
$emailService = new EmailService();
$settingModel = new Setting();
$success = false;
$error = '';

// Fetch company contact details from settings
$settings = $settingModel->getMultiple([
    'site_name',
    'contact_email',
    'contact_phone',
    'contact_address',
    'business_hours_weekday',
    'business_hours_weekend'
]);

$site_name = $settings['site_name'] ?? 'SellerPortal';
$contact_email = $settings['contact_email'] ?? '';
$contact_phone = $settings['contact_phone'] ?? '';
$contact_address = $settings['contact_address'] ?? '';
$business_hours_weekday = $settings['business_hours_weekday'] ?? 'Monday - Friday: 9:00 AM - 6:00 PM';
$business_hours_weekend = $settings['business_hours_weekend'] ?? 'Saturday - Sunday: Closed';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        // Validate required fields
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($message)) {
            throw new Exception('Please fill in all required fields.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        // Create lead
        $leadModel = new Lead();
        $leadData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'source' => 'CONTACT_FORM',
            'status' => 'NEW'
        ];
        
        $lead = $leadModel->create($leadData);
        
        if ($lead) {
            // Send email notification to admin
            $emailService->sendContactFormNotification($leadData);
            
            $success = true;
            // Clear form data
            $_POST = [];
        } else {
            throw new Exception('Failed to submit your message. Please try again.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Generate CSRF token
$csrfToken = $csrfService->generateToken();

// Set page variables
$page_title = 'Contact Us';
$page_description = 'Get in touch with the Karyalay team';

// Include header
include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="contact-hero">
    <div class="container">
        <div class="contact-hero-content">
            <h1 class="contact-hero-title">Contact Us</h1>
            <p class="contact-hero-subtitle">
                Have questions? We'd love to hear from you. Get in touch with our team.
            </p>
        </div>
    </div>
</section>

<!-- Quick Help Section -->
<section class="contact-quick-section">
    <div class="container">
        <div class="contact-quick-grid">
            <!-- Existing Customer Card -->
            <div class="contact-quick-card">
                <div class="contact-quick-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                </div>
                <h3 class="contact-quick-title">Existing Customer?</h3>
                <p class="contact-quick-text">
                    For faster support, please use our ticket system. Our support team monitors tickets and responds promptly.
                </p>
                <?php if (isAuthenticated()): ?>
                    <a href="<?php echo get_app_base_url(); ?>/app/support/tickets/new.php" class="btn btn-primary">Create Support Ticket</a>
                <?php else: ?>
                    <a href="<?php echo get_base_url(); ?>/login.php" class="btn btn-primary">Login to Create Ticket</a>
                <?php endif; ?>
            </div>

            <!-- FAQ Card -->
            <div class="contact-quick-card">
                <div class="contact-quick-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="contact-quick-title">Common Questions?</h3>
                <p class="contact-quick-text">
                    Check our FAQ section for quick answers to frequently asked questions about our services and features.
                </p>
                <a href="<?php echo get_base_url(); ?>/support.php" class="btn btn-outline">View FAQs</a>
            </div>
        </div>
    </div>
</section>

<!-- Contact Details & Form Section -->
<section class="contact-main-section">
    <div class="container">
        <div class="contact-main-grid">
            <!-- Contact Information -->
            <div class="contact-info-panel">
                <div class="contact-section-header">
                    <span class="contact-section-label">Get in Touch</span>
                    <h2 class="contact-section-title">Contact Information</h2>
                    <p class="contact-section-subtitle">
                        For sales inquiries, partnerships, or general questions, reach out to us directly.
                    </p>
                </div>

                <div class="contact-details-list">
                    <?php if (!empty($contact_email)): ?>
                    <div class="contact-detail-item">
                        <div class="contact-detail-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="contact-detail-content">
                            <h4 class="contact-detail-label">Email</h4>
                            <a href="mailto:<?php echo htmlspecialchars($contact_email); ?>" class="contact-detail-value">
                                <?php echo htmlspecialchars($contact_email); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($contact_phone)): ?>
                    <div class="contact-detail-item">
                        <div class="contact-detail-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <div class="contact-detail-content">
                            <h4 class="contact-detail-label">Phone</h4>
                            <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $contact_phone)); ?>" class="contact-detail-value">
                                <?php echo htmlspecialchars($contact_phone); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($contact_address)): ?>
                    <div class="contact-detail-item">
                        <div class="contact-detail-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div class="contact-detail-content">
                            <h4 class="contact-detail-label">Address</h4>
                            <p class="contact-detail-value"><?php echo nl2br(htmlspecialchars($contact_address)); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Business Hours -->
                <div class="contact-hours-card">
                    <div class="contact-hours-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="contact-hours-title">Business Hours</h4>
                    <p class="contact-hours-text"><?php echo htmlspecialchars($business_hours_weekday); ?></p>
                    <p class="contact-hours-text"><?php echo htmlspecialchars($business_hours_weekend); ?></p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form-panel">
                <?php if ($success): ?>
                    <div class="contact-alert contact-alert-success">
                        <div class="contact-alert-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="contact-alert-content">
                            <h3 class="contact-alert-title">Thank You!</h3>
                            <p class="contact-alert-text">Your message has been received. We'll get back to you as soon as possible.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="contact-alert contact-alert-error">
                        <div class="contact-alert-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="contact-alert-content">
                            <p class="contact-alert-text"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="contact-form-card">
                    <h3 class="contact-form-title">Send us a Message</h3>
                    <p class="contact-form-subtitle">Fill out the form below and we'll respond within 24-48 hours.</p>
                        
                    <form method="POST" action="<?php echo get_base_url(); ?>/contact.php" class="contact-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="contact-form-group">
                            <label for="name" class="contact-form-label">
                                Name <span class="contact-form-required">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   required
                                   aria-required="true"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                   class="contact-form-input"
                                   placeholder="Your full name">
                        </div>
                        
                        <div class="contact-form-group">
                            <label for="email" class="contact-form-label">
                                Email <span class="contact-form-required">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   required
                                   aria-required="true"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   class="contact-form-input"
                                   placeholder="your@email.com">
                        </div>
                        
                        <div class="contact-form-group">
                            <label for="phone-input" class="contact-form-label">
                                Phone
                            </label>
                            <?php echo render_phone_input([
                                'id' => 'phone',
                                'name' => 'phone',
                                'value' => $_POST['phone'] ?? '',
                                'required' => false,
                            ]); ?>
                        </div>
                        
                        <div class="contact-form-group">
                            <label for="message" class="contact-form-label">
                                Message <span class="contact-form-required">*</span>
                            </label>
                            <textarea id="message" 
                                      name="message" 
                                      required
                                      aria-required="true"
                                      rows="5"
                                      class="contact-form-textarea"
                                      placeholder="How can we help you?"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            Send Message
                        </button>
                    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Contact Page Styles - Matching About/Features Theme */
.contact-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0 60px;
    text-align: center;
    color: white;
}

.contact-hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.contact-hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin: 0 0 20px;
    line-height: 1.2;
}

.contact-hero-subtitle {
    font-size: 1.25rem;
    opacity: 0.95;
    line-height: 1.6;
    margin: 0;
}

/* Quick Help Section */
.contact-quick-section {
    padding: 60px 0;
    background: #ffffff;
}

.contact-quick-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    max-width: 900px;
    margin: 0 auto;
}

.contact-quick-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 40px 30px;
    text-align: center;
    transition: all 0.3s ease;
}

.contact-quick-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.contact-quick-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.contact-quick-icon svg {
    width: 32px;
    height: 32px;
    color: white;
}

.contact-quick-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 12px;
}

.contact-quick-text {
    font-size: 1rem;
    color: #4a5568;
    line-height: 1.6;
    margin: 0 0 24px;
}

/* Main Contact Section */
.contact-main-section {
    padding: 60px 0;
    background: #f8f9fa;
}

.contact-main-grid {
    display: grid;
    grid-template-columns: 1fr 1.3fr;
    gap: 50px;
    max-width: 1200px;
    margin: 0 auto;
}

/* Contact Info Panel */
.contact-section-header {
    margin-bottom: 40px;
}

.contact-section-label {
    display: inline-block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #667eea;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-bottom: 10px;
}

.contact-section-title {
    font-size: 2.25rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0 0 15px;
    line-height: 1.2;
}

.contact-section-subtitle {
    font-size: 1.125rem;
    color: #4a5568;
    line-height: 1.6;
    margin: 0;
}

.contact-details-list {
    display: flex;
    flex-direction: column;
    gap: 25px;
    margin-bottom: 40px;
}

.contact-detail-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.contact-detail-icon {
    width: 50px;
    height: 50px;
    min-width: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.contact-detail-icon svg {
    width: 24px;
    height: 24px;
    color: white;
}

.contact-detail-content {
    flex: 1;
    padding-top: 5px;
}

.contact-detail-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0 0 8px;
}

.contact-detail-value {
    font-size: 1.125rem;
    color: #1a202c;
    line-height: 1.5;
    margin: 0;
}

.contact-detail-value a {
    color: #667eea;
    text-decoration: none;
    transition: color 0.2s;
}

.contact-detail-value a:hover {
    color: #764ba2;
    text-decoration: underline;
}

/* Business Hours Card */
.contact-hours-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 30px;
}

.contact-hours-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.contact-hours-icon svg {
    width: 24px;
    height: 24px;
    color: white;
}

.contact-hours-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 15px;
}

.contact-hours-text {
    font-size: 1rem;
    color: #4a5568;
    margin: 0 0 8px;
    line-height: 1.5;
}

.contact-hours-text:last-child {
    margin-bottom: 0;
}

/* Contact Form Panel */
.contact-form-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.contact-form-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #1a202c;
    margin: 0 0 10px;
}

.contact-form-subtitle {
    font-size: 1rem;
    color: #4a5568;
    margin: 0 0 30px;
}

.contact-form-group {
    margin-bottom: 24px;
}

.contact-form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 8px;
}

.contact-form-required {
    color: #e53e3e;
}

.contact-form-input,
.contact-form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #cbd5e0;
    border-radius: 8px;
    font-size: 1rem;
    color: #1a202c;
    background: white;
    transition: all 0.2s;
    box-sizing: border-box;
}

.contact-form-input:focus,
.contact-form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.contact-form-textarea {
    resize: vertical;
    min-height: 120px;
    font-family: inherit;
}

/* Alerts */
.contact-alert {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.contact-alert-success {
    background: #d1fae5;
    border: 1px solid #6ee7b7;
}

.contact-alert-error {
    background: #fee2e2;
    border: 1px solid #fca5a5;
}

.contact-alert-icon {
    flex-shrink: 0;
}

.contact-alert-icon svg {
    width: 24px;
    height: 24px;
}

.contact-alert-success .contact-alert-icon svg {
    color: #059669;
}

.contact-alert-error .contact-alert-icon svg {
    color: #dc2626;
}

.contact-alert-content {
    flex: 1;
}

.contact-alert-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 5px;
}

.contact-alert-success .contact-alert-title {
    color: #065f46;
}

.contact-alert-text {
    font-size: 0.9375rem;
    margin: 0;
}

.contact-alert-success .contact-alert-text {
    color: #047857;
}

.contact-alert-error .contact-alert-text {
    color: #991b1b;
}

/* Responsive */
@media (max-width: 768px) {
    .contact-hero-title {
        font-size: 2rem;
    }
    
    .contact-hero-subtitle {
        font-size: 1.125rem;
    }
    
    .contact-quick-grid {
        grid-template-columns: 1fr;
    }
    
    .contact-main-grid {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .contact-section-title {
        font-size: 1.75rem;
    }
    
    .contact-form-card {
        padding: 30px 20px;
    }
}
</style>

<?php
// Include footer
include_footer();
?>
