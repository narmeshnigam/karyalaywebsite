<?php

/**
 * Karyalay Portal System
 * Demo Booking Page
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
use Karyalay\Services\CsrfService;
use Karyalay\Services\EmailService;

$csrfService = new CsrfService();
$emailService = new EmailService();
$success = false;
$error = '';

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
        $companyName = trim($_POST['company_name'] ?? '');
        $preferredDate = trim($_POST['preferred_date'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        if (empty($name) || empty($email) || empty($companyName)) {
            throw new Exception('Please fill in all required fields.');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        
        // Create demo request lead
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
            // Send email notification to admin
            $emailService->sendDemoRequestNotification($leadData);
            
            $success = true;
            // Clear form data
            $_POST = [];
        } else {
            throw new Exception('Failed to submit your demo request. Please try again.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Generate CSRF token
$csrfToken = $csrfService->generateToken();

// Set page variables
$page_title = 'Request a Demo';
$page_description = 'See Karyalay in action with a personalized demo';

// Include header
include_header($page_title, $page_description);
?>

<!-- Page Header -->
<section class="section bg-gray-50">
    <div class="container">
        <h1 class="text-4xl font-bold mb-4">Request a Demo</h1>
        <p class="text-xl text-gray-600">
            See how Karyalay can transform your business operations
        </p>
    </div>
</section>

<!-- Demo Request Form -->
<section class="section">
    <div class="container">
        <div class="max-w-2xl mx-auto">
            <?php if ($success): ?>
                <div class="card bg-green-50 border-green-200 mb-6">
                    <div class="card-body">
                        <h3 class="text-xl font-semibold text-green-800 mb-2">Demo Request Received!</h3>
                        <p class="text-green-700">
                            Thank you for your interest. Our team will contact you shortly to schedule your personalized demo.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="card bg-red-50 border-red-200 mb-6">
                    <div class="card-body">
                        <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="/demo.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Name <span class="text-red-500" aria-label="required">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   required
                                   aria-required="true"
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email <span class="text-red-500" aria-label="required">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   required
                                   aria-required="true"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div class="mb-4">
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div class="mb-4">
                            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Company Name <span class="text-red-500" aria-label="required">*</span>
                            </label>
                            <input type="text" 
                                   id="company_name" 
                                   name="company_name" 
                                   required
                                   aria-required="true"
                                   value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div class="mb-4">
                            <label for="preferred_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Preferred Date
                            </label>
                            <input type="date" 
                                   id="preferred_date" 
                                   name="preferred_date"
                                   value="<?php echo htmlspecialchars($_POST['preferred_date'] ?? ''); ?>"
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div class="mb-6">
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Additional Notes
                            </label>
                            <textarea id="notes" 
                                      name="notes" 
                                      rows="4"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            Request Demo
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_footer();
?>
