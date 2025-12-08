<?php

/**
 * Karyalay Portal System
 * Verify Payment
 * 
 * Verifies payment signature and redirects to success page
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
    $_SESSION['error'] = 'Session expired. Please log in.';
    header('Location: /karyalayportal/login.php');
    exit;
}

use Karyalay\Services\PaymentService;

try {
    // Validate required parameters
    if (empty($_GET['razorpay_payment_id']) || empty($_GET['razorpay_order_id']) || empty($_GET['razorpay_signature'])) {
        $_SESSION['error'] = 'Invalid payment verification request.';
        header('Location: /karyalayportal/checkout.php');
        exit;
    }
    
    // Verify payment signature
    $paymentService = new PaymentService();
    $attributes = [
        'razorpay_order_id' => $_GET['razorpay_order_id'],
        'razorpay_payment_id' => $_GET['razorpay_payment_id'],
        'razorpay_signature' => $_GET['razorpay_signature']
    ];
    
    if (!$paymentService->verifyPaymentSignature($attributes)) {
        $_SESSION['error'] = 'Payment verification failed. Please contact support.';
        header('Location: /karyalayportal/payment-failed.php');
        exit;
    }
    
    // Clear session data
    unset($_SESSION['pending_order_id']);
    unset($_SESSION['razorpay_order_id']);
    unset($_SESSION['selected_plan_id']);
    unset($_SESSION['selected_plan_slug']);
    
    // Redirect to success page
    $_SESSION['success'] = 'Payment successful! Your subscription is being activated.';
    header('Location: /karyalayportal/payment-success.php?payment_id=' . urlencode($_GET['razorpay_payment_id']));
    exit;
    
} catch (Exception $e) {
    error_log('Payment verification error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred during payment verification.';
    header('Location: /karyalayportal/payment-failed.php');
    exit;
}

