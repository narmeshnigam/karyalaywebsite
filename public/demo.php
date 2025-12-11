<?php
/**
 * SellerPortal System
 * Demo Booking Page - Redesigned
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

use Karyalay\Models\Lead;
use Karyalay\Services\CsrfService;
use Karyalay\Services\EmailService;

$csrfService = new CsrfService();
$emailService = new EmailService();
$success = false;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$csrfService->validateToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please try again.');
        }
        
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $preferredDate = trim($_POST['preferred_date'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($name) || empty($email) || empty($companyName)) {
            throw new Exception('Please fill in all required fields.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        $leadModel = new Lead();
        $leadData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $notes,
            'company_name' => $companyName,
            'preferred_date' => !empty($preferredDate) ? $preferredDate : null,
            'source' => 'DEMO_REQUEST',
            'status' => 'NEW'
        ];
        
        $lead = $leadModel->create($leadData);
        
        if ($lead) {
            // Send thank you email to the user
            try {
                $emailService->sendLeadThankYouEmail($email, $name);
            } catch (Exception $e) {
                error_log('Failed to send thank you email: ' . $e->getMessage());
            }
            
            // Send notification email to admin
            try {
                $emailService->sendDemoRequestNotification($leadData);
            } catch (Exception $e) {
                error_log('Failed to send admin notification email: ' . $e->getMessage());
            }
            
            $success = true;
            $_POST = [];
        } else {
            throw new Exception('Failed to submit your demo request. Please try again.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$csrfToken = $csrfService->generateToken();
$page_title = 'Request a Demo';
$page_description = 'See ' . get_brand_name() . ' in action with a personalized demo';
include_header($page_title, $page_description);
?>

<!-- Hero Section -->
<section class="demo-hero">
    <div class="container">
        <div class="demo-hero-content">
            <h1 class="demo-hero-title">Request a Demo</h1>
            <p class="demo-hero-subtitle">
                See how <?php echo get_brand_name(); ?> can transform your business operations with a personalized walkthrough
            </p>
        </div>
    </div>
</section>

<!-- What You'll Learn Section -->
<section class="demo-benefits-section">
    <div class="container">
        <div class="demo-section-header">
            <h2 class="demo-section-title">What You'll Discover</h2>
            <p class="demo-section-subtitle">Our experts will walk you through the platform's key capabilities</p>
        </div>
        
        <div class="demo-benefits-grid">
            <div class="demo-benefit-card">
                <div class="demo-benefit-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="demo-benefit-title">Core Features</h3>
                <p class="demo-benefit-text">Explore the powerful features that streamline your business operations.</p>
            </div>
            
            <div class="demo-benefit-card">
                <div class="demo-benefit-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                </div>
                <h3 class="demo-benefit-title">Customization Options</h3>
                <p class="demo-benefit-text">Learn how to tailor the platform to your unique requirements.</p>
            </div>
            
            <div class="demo-benefit-card">
                <div class="demo-benefit-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="demo-benefit-title">Analytics & Insights</h3>
                <p class="demo-benefit-text">Discover reporting capabilities that drive better business decisions.</p>
            </div>
        </div>
    </div>
</section>



<!-- Demo Request Form Section -->
<section class="demo-form-section">
    <div class="container">
        <div class="demo-form-wrapper">
            <div class="demo-form-content">
                <div class="demo-form-header">
                    <h2 class="demo-form-title">Book Your Free Demo</h2>
                    <p class="demo-form-subtitle">Fill out the form below and we'll get back to you within 24 hours</p>
                </div>
                
                <?php if ($success): ?>
                    <div class="demo-alert demo-alert-success">
                        <div class="demo-alert-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="demo-alert-content">
                            <h3 class="demo-alert-title">Demo Request Received!</h3>
                            <p class="demo-alert-text">Thank you for your interest. Our team will contact you shortly to schedule your personalized demo.</p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="demo-alert demo-alert-error">
                        <div class="demo-alert-icon">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="demo-alert-content">
                            <p class="demo-alert-text"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo get_base_url(); ?>/demo.php" class="demo-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <div class="demo-form-row">
                        <div class="demo-form-group">
                            <label for="name" class="demo-form-label">
                                Full Name <span class="demo-required">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   required
                                   aria-required="true"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                   class="demo-form-input"
                                   placeholder="John Doe">
                        </div>
                        
                        <div class="demo-form-group">
                            <label for="email" class="demo-form-label">
                                Email Address <span class="demo-required">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   required
                                   aria-required="true"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   class="demo-form-input"
                                   placeholder="john@company.com">
                        </div>
                    </div>
                    
                    <div class="demo-form-row">
                        <div class="demo-form-group">
                            <label for="phone-input" class="demo-form-label">
                                Phone Number
                            </label>
                            <?php echo render_phone_input([
                                'id' => 'phone',
                                'name' => 'phone',
                                'value' => $_POST['phone'] ?? '',
                                'required' => false,
                            ]); ?>
                        </div>
                        
                        <div class="demo-form-group">
                            <label for="company_name" class="demo-form-label">
                                Company Name <span class="demo-required">*</span>
                            </label>
                            <input type="text" 
                                   id="company_name" 
                                   name="company_name" 
                                   required
                                   aria-required="true"
                                   value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>"
                                   class="demo-form-input"
                                   placeholder="Your Company">
                        </div>
                    </div>
                    
                    <div class="demo-form-group">
                        <label for="preferred_date" class="demo-form-label">
                            Preferred Date
                        </label>
                        <input type="date" 
                               id="preferred_date" 
                               name="preferred_date"
                               value="<?php echo htmlspecialchars($_POST['preferred_date'] ?? ''); ?>"
                               min="<?php echo date('Y-m-d'); ?>"
                               class="demo-form-input">
                        <span class="demo-form-help">Select your preferred date for the demo session</span>
                    </div>
                    
                    <div class="demo-form-group">
                        <label for="notes" class="demo-form-label">
                            Tell Us About Your Needs
                        </label>
                        <textarea id="notes" 
                                  name="notes" 
                                  rows="4"
                                  class="demo-form-textarea"
                                  placeholder="What specific features or use cases are you interested in?"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        <span class="demo-form-help">Optional: Help us prepare a more relevant demo for you</span>
                    </div>
                    
                    <button type="submit" class="demo-form-submit">
                        <span>Request Your Free Demo</span>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </form>
            </div>
            
            <div class="demo-form-sidebar">
                <div class="demo-sidebar-card">
                    <h3 class="demo-sidebar-title">What to Expect</h3>
                    <ul class="demo-sidebar-list">
                        <li>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>30-minute personalized session</span>
                        </li>
                        <li>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Live platform walkthrough</span>
                        </li>
                        <li>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Q&A with product expert</span>
                        </li>
                        <li>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>No commitment required</span>
                        </li>
                    </ul>
                </div>
                
                <div class="demo-sidebar-info">
                    <svg class="demo-info-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>We'll respond within 24 hours to schedule your demo at a convenient time.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Demo Page Styles */

/* Hero Section */
.demo-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: var(--spacing-16) 0;
    position: relative;
    overflow: hidden;
}

.demo-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 30% 40%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 70%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
    pointer-events: none;
}

.demo-hero-content {
    position: relative;
    z-index: 1;
    text-align: center;
    max-width: 700px;
    margin: 0 auto;
}

.demo-hero-title {
    font-size: var(--font-size-4xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-white);
    margin-bottom: var(--spacing-4);
    line-height: 1.2;
}

.demo-hero-subtitle {
    font-size: var(--font-size-lg);
    color: rgba(255, 255, 255, 0.95);
    line-height: 1.6;
}

/* Benefits Section */
.demo-benefits-section {
    padding: var(--spacing-16) 0;
    background: var(--color-gray-50);
}

.demo-section-header {
    text-align: center;
    max-width: 700px;
    margin: 0 auto var(--spacing-12);
}

.demo-section-title {
    font-size: var(--font-size-3xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin-bottom: var(--spacing-3);
}

.demo-section-subtitle {
    font-size: var(--font-size-lg);
    color: var(--color-gray-600);
}

.demo-benefits-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-6);
    max-width: 1000px;
    margin: 0 auto;
}

.demo-benefit-card {
    background: var(--color-white);
    padding: var(--spacing-6);
    border-radius: var(--radius-xl);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.demo-benefit-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.demo-benefit-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: var(--radius-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-4);
}

.demo-benefit-icon svg {
    width: 28px;
    height: 28px;
    color: var(--color-white);
}

.demo-benefit-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin-bottom: var(--spacing-2);
}

.demo-benefit-text {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
    line-height: 1.6;
}



/* Form Section */
.demo-form-section {
    padding: var(--spacing-16) 0;
    background: linear-gradient(180deg, var(--color-gray-50) 0%, var(--color-white) 100%);
}

.demo-form-wrapper {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-10);
    max-width: 1200px;
    margin: 0 auto;
}

.demo-form-content {
    background: var(--color-white);
    padding: var(--spacing-8);
    border-radius: var(--radius-2xl);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.demo-form-header {
    margin-bottom: var(--spacing-8);
}

.demo-form-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-gray-900);
    margin-bottom: var(--spacing-2);
}

.demo-form-subtitle {
    font-size: var(--font-size-base);
    color: var(--color-gray-600);
}

.demo-alert {
    display: flex;
    gap: var(--spacing-4);
    padding: var(--spacing-4);
    border-radius: var(--radius-lg);
    margin-bottom: var(--spacing-6);
}

.demo-alert-success {
    background: #f0fdf4;
    border: 1px solid #86efac;
}

.demo-alert-error {
    background: #fef2f2;
    border: 1px solid #fca5a5;
}

.demo-alert-icon {
    flex-shrink: 0;
}

.demo-alert-success .demo-alert-icon {
    color: #16a34a;
}

.demo-alert-error .demo-alert-icon {
    color: #dc2626;
}

.demo-alert-icon svg {
    width: 24px;
    height: 24px;
}

.demo-alert-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    margin-bottom: var(--spacing-1);
}

.demo-alert-success .demo-alert-title {
    color: #15803d;
}

.demo-alert-text {
    font-size: var(--font-size-base);
    line-height: 1.5;
}

.demo-alert-success .demo-alert-text {
    color: #166534;
}

.demo-alert-error .demo-alert-text {
    color: #991b1b;
}

.demo-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-4);
    margin-bottom: var(--spacing-4);
}

.demo-form-group {
    margin-bottom: var(--spacing-4);
}

.demo-form-label {
    display: block;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-700);
    margin-bottom: var(--spacing-2);
}

.demo-required {
    color: #ef4444;
}

.demo-form-input,
.demo-form-textarea {
    width: 100%;
    padding: var(--spacing-3);
    border: 2px solid var(--color-gray-200);
    border-radius: var(--radius-lg);
    font-size: var(--font-size-base);
    transition: all 0.2s;
    font-family: inherit;
}

.demo-form-input:focus,
.demo-form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.demo-form-textarea {
    resize: vertical;
}

.demo-form-help {
    display: block;
    font-size: var(--font-size-sm);
    color: var(--color-gray-500);
    margin-top: var(--spacing-1);
}

.demo-form-submit {
    width: 100%;
    padding: var(--spacing-4);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: var(--color-white);
    border: none;
    border-radius: var(--radius-lg);
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-2);
    margin-top: var(--spacing-6);
}

.demo-form-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

/* Sidebar */
.demo-form-sidebar {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-6);
}

.demo-sidebar-card {
    background: var(--color-white);
    padding: var(--spacing-6);
    border-radius: var(--radius-xl);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.demo-sidebar-title {
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-gray-900);
    margin-bottom: var(--spacing-4);
}

.demo-sidebar-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.demo-sidebar-list li {
    display: flex;
    align-items: flex-start;
    gap: var(--spacing-3);
    margin-bottom: var(--spacing-3);
}

.demo-sidebar-list li:last-child {
    margin-bottom: 0;
}

.demo-sidebar-list svg {
    width: 20px;
    height: 20px;
    color: #667eea;
    flex-shrink: 0;
    margin-top: 2px;
}

.demo-sidebar-list span {
    font-size: var(--font-size-base);
    color: var(--color-gray-700);
    line-height: 1.5;
}

.demo-sidebar-info {
    background: #f0f9ff;
    border: 1px solid #bfdbfe;
    border-radius: var(--radius-lg);
    padding: var(--spacing-4);
    display: flex;
    gap: var(--spacing-3);
}

.demo-info-icon {
    width: 20px;
    height: 20px;
    color: #3b82f6;
    flex-shrink: 0;
    margin-top: 2px;
}

.demo-sidebar-info p {
    font-size: var(--font-size-sm);
    color: #1e40af;
    line-height: 1.5;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .demo-form-wrapper {
        grid-template-columns: 1fr;
    }
    
    .demo-form-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .demo-hero-title {
        font-size: var(--font-size-3xl);
    }
    
    .demo-hero-subtitle {
        font-size: var(--font-size-base);
    }
    
    .demo-section-title {
        font-size: var(--font-size-2xl);
    }
    
    .demo-benefits-grid {
        grid-template-columns: 1fr;
    }
    
    .demo-form-row {
        grid-template-columns: 1fr;
    }
    
    .demo-form-content {
        padding: var(--spacing-6);
    }
}

@media (max-width: 480px) {
    .demo-hero {
        padding: var(--spacing-12) 0;
    }
    
    .demo-hero-title {
        font-size: var(--font-size-2xl);
    }
    
    .demo-hero-subtitle {
        font-size: var(--font-size-sm);
    }
}
</style>

<?php include_footer(); ?>
