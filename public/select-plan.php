<?php

/**
 * SellerPortal System
 * Plan Selection Handler
 * 
 * Stores selected plan in session and redirects to checkout
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

// Include template helpers for redirect function
require_once __DIR__ . '/../includes/template_helpers.php';

// Check if user is logged in
if (!isAuthenticated()) {
    $_SESSION['error'] = 'Please log in to purchase a plan.';
    redirect('/login.php');
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pricing.php');
}

// Get plan slug from POST data
$planSlug = $_POST['plan_slug'] ?? '';

if (empty($planSlug)) {
    $_SESSION['error'] = 'Please select a plan.';
    redirect('/pricing.php');
}

// Validate plan exists
use Karyalay\Models\Plan;
use Karyalay\Services\PortAvailabilityService;

try {
    $planModel = new Plan();
    $plan = $planModel->findBySlug($planSlug);
    
    if (!$plan) {
        $_SESSION['error'] = 'Invalid plan selected.';
        redirect('/pricing.php');
    }
    
    if ($plan['status'] !== 'ACTIVE') {
        $_SESSION['error'] = 'This plan is not currently available.';
        redirect('/pricing.php');
    }
    
    // Check port availability (plan-agnostic)
    $portAvailabilityService = new PortAvailabilityService();
    $availabilityCheck = $portAvailabilityService->checkAvailability();
    
    if (!$availabilityCheck['available']) {
        $_SESSION['error'] = 'No available ports. Please contact support.';
        redirect('/pricing.php');
    }
    
    // Store plan ID in session
    $_SESSION['selected_plan_id'] = $plan['id'];
    $_SESSION['selected_plan_slug'] = $plan['slug'];
    
    // Redirect to checkout
    redirect('/checkout.php');
    
} catch (Exception $e) {
    error_log('Plan selection error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred. Please try again.';
    redirect('/pricing.php');
}
