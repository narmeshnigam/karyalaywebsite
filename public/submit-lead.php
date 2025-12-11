<?php
/**
 * Lead Form Submission Handler
 * Processes lead capture form submissions via AJAX
 */

// Set JSON response header first
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Load authentication helpers
require_once __DIR__ . '/../includes/auth_helpers.php';

// Load Lead model and EmailService
use Karyalay\Models\Lead;
use Karyalay\Services\EmailService;

// Start secure session
startSecureSession();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Verify CSRF token
    if (!validateCsrfToken()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid security token. Please refresh the page and try again.'
        ]);
        exit;
    }
    
    // Validate required fields
    $errors = [];
    
    if (empty($_POST['name']) || trim($_POST['name']) === '') {
        $errors['name'] = 'Name is required';
    }
    
    if (empty($_POST['email']) || trim($_POST['email']) === '') {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    // Validate phone if provided
    if (!empty($_POST['phone'])) {
        $phone = preg_replace('/[^0-9+\-() ]/', '', $_POST['phone']);
        if (strlen($phone) < 10) {
            $errors['phone'] = 'Please enter a valid phone number';
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please check your input and try again.',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Sanitize and prepare data
    $leadData = [
        'name' => trim($_POST['name']),
        'email' => trim(strtolower($_POST['email'])),
        'phone' => !empty($_POST['phone']) ? trim($_POST['phone']) : null,
        'company' => !empty($_POST['company']) ? trim($_POST['company']) : null,
        'message' => !empty($_POST['message']) ? trim($_POST['message']) : null,
        'source' => 'CONTACT_FORM' // Valid ENUM value
    ];
    
    // Create lead
    $leadModel = new Lead();
    $lead = $leadModel->create($leadData);
    
    if ($lead) {
        // Send emails
        $emailService = new EmailService();
        
        // Send thank you email to the lead
        try {
            $emailService->sendLeadThankYouEmail($leadData['email'], $leadData['name']);
        } catch (Exception $e) {
            error_log('Failed to send thank you email: ' . $e->getMessage());
            // Don't fail the request if email fails
        }
        
        // Send notification email to admin
        try {
            $emailService->sendLeadNotification($leadData);
        } catch (Exception $e) {
            error_log('Failed to send admin notification email: ' . $e->getMessage());
            // Don't fail the request if email fails
        }
        
        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Thank you for your interest! We\'ll be in touch with you shortly.'
        ]);
    } else {
        // Database error - log the data that failed
        error_log('Failed to create lead with data: ' . json_encode($leadData));
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'We encountered an issue processing your request. Please try again or contact us directly.',
            'debug' => $config['debug'] ? 'Database insert failed' : null
        ]);
    }
    
} catch (Exception $e) {
    // Log error with full details
    error_log('Lead submission error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Return detailed error in debug mode
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.',
        'debug' => $config['debug'] ? $e->getMessage() : null
    ]);
}
