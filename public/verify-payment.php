<?php

/**
 * SellerPortal System
 * Verify Payment
 * 
 * Verifies payment signature, creates subscription, allocates port, and redirects to success page
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
require_once __DIR__ . '/../includes/template_helpers.php';

// Start secure session
startSecureSession();

$baseUrl = get_base_url();

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = 'Session expired. Please log in.';
    header('Location: ' . $baseUrl . '/login.php');
    exit;
}

use Karyalay\Services\PaymentService;
use Karyalay\Services\SubscriptionService;
use Karyalay\Models\Order;
use Karyalay\Models\Subscription;
use Karyalay\Models\Plan;

try {
    // Validate required parameters
    if (empty($_GET['razorpay_payment_id']) || empty($_GET['razorpay_order_id']) || empty($_GET['razorpay_signature'])) {
        $_SESSION['error'] = 'Invalid payment verification request.';
        header('Location: ' . $baseUrl . '/checkout.php');
        exit;
    }
    
    $razorpayPaymentId = $_GET['razorpay_payment_id'];
    $razorpayOrderId = $_GET['razorpay_order_id'];
    $razorpaySignature = $_GET['razorpay_signature'];
    
    error_log("Verify Payment: Starting verification for payment {$razorpayPaymentId}");
    
    // Verify payment signature
    $paymentService = new PaymentService();
    $attributes = [
        'razorpay_order_id' => $razorpayOrderId,
        'razorpay_payment_id' => $razorpayPaymentId,
        'razorpay_signature' => $razorpaySignature
    ];
    
    if (!$paymentService->verifyPaymentSignature($attributes)) {
        error_log("Verify Payment: Signature verification failed for payment {$razorpayPaymentId}");
        $_SESSION['error'] = 'Payment verification failed. Please contact support.';
        header('Location: ' . $baseUrl . '/payment-failed.php');
        exit;
    }
    
    error_log("Verify Payment: Signature verified for payment {$razorpayPaymentId}");
    
    // Find the order by Razorpay order ID
    $orderModel = new Order();
    $order = $orderModel->findByPgOrderId($razorpayOrderId);
    
    if (!$order) {
        error_log("Verify Payment: Order not found for Razorpay order {$razorpayOrderId}");
        $_SESSION['error'] = 'Order not found. Please contact support.';
        header('Location: ' . $baseUrl . '/payment-failed.php');
        exit;
    }
    
    error_log("Verify Payment: Found order {$order['id']} for Razorpay order {$razorpayOrderId}");
    
    // Update order with payment ID
    $orderModel->update($order['id'], [
        'pg_payment_id' => $razorpayPaymentId
    ]);
    
    // Check if this is a renewal
    $isRenewal = isset($_SESSION['is_renewal']) && $_SESSION['is_renewal'] === true;
    $renewalSubscriptionId = $_SESSION['renewal_subscription_id'] ?? null;
    
    if ($isRenewal && $renewalSubscriptionId) {
        // Handle renewal - extend existing subscription
        error_log("Verify Payment: Processing renewal for subscription {$renewalSubscriptionId}");
        
        $subscriptionModel = new Subscription();
        $subscription = $subscriptionModel->findById($renewalSubscriptionId);
        
        if ($subscription) {
            // Update order status to SUCCESS
            $orderModel->updateStatus($order['id'], 'SUCCESS');
            
            // Extend subscription end date
            $planModel = new Plan();
            $plan = $planModel->findById($order['plan_id']);
            
            if ($plan) {
                $currentEndDate = $subscription['end_date'] ? strtotime($subscription['end_date']) : time();
                $newEndDate = date('Y-m-d H:i:s', strtotime("+{$plan['billing_period_months']} months", $currentEndDate));
                
                $subscriptionModel->update($renewalSubscriptionId, [
                    'end_date' => $newEndDate,
                    'status' => 'ACTIVE'
                ]);
                
                error_log("Verify Payment: Subscription {$renewalSubscriptionId} renewed until {$newEndDate}");
                
                // Clear renewal session data
                unset($_SESSION['is_renewal']);
                unset($_SESSION['renewal_subscription_id']);
                
                $_SESSION['success'] = 'Payment successful! Your subscription has been renewed.';
                header('Location: ' . $baseUrl . '/payment-success.php?payment_id=' . urlencode($razorpayPaymentId));
                exit;
            }
        }
        
        error_log("Verify Payment: Failed to process renewal");
        $_SESSION['error'] = 'Payment was successful but there was an issue renewing your subscription. Please contact support.';
        header('Location: ' . $baseUrl . '/payment-success.php?payment_id=' . urlencode($razorpayPaymentId) . '&status=pending');
        exit;
    }
    
    // Process the successful payment - create subscription and allocate port (for new subscriptions)
    $subscriptionService = new SubscriptionService();
    $result = $subscriptionService->processSuccessfulPayment($order['id'], $razorpayPaymentId);
    
    if (!$result['success']) {
        error_log("Verify Payment: Failed to process payment - " . ($result['error'] ?? 'Unknown error'));
        $_SESSION['error'] = 'Payment was successful but there was an issue activating your subscription. Please contact support.';
        // Still redirect to success since payment was successful
        header('Location: ' . $baseUrl . '/payment-success.php?payment_id=' . urlencode($razorpayPaymentId) . '&status=pending');
        exit;
    }
    
    error_log("Verify Payment: Payment processed successfully for order {$order['id']}");
    
    // Send payment confirmation emails
    try {
        error_log('Verify Payment: Sending confirmation emails');
        
        $emailService = new \Karyalay\Services\EmailService();
        $userModel = new \Karyalay\Models\User();
        $planModel = new \Karyalay\Models\Plan();
        
        $customer = $userModel->findById($order['customer_id']);
        $plan = $planModel->findById($order['plan_id']);
        
        if ($customer && $plan) {
            // Generate invoice URL
            $invoiceUrl = $baseUrl . '/app/billing/invoice.php?id=' . $order['id'];
            
            // Send customer payment success email
            $paymentData = [
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'plan_name' => $plan['name'],
                'amount' => number_format($order['amount'], 2),
                'currency' => $order['currency'] ?? 'INR',
                'order_id' => substr($order['id'], 0, 8),
                'payment_id' => $razorpayPaymentId,
                'invoice_url' => $invoiceUrl
            ];
            
            error_log('Verify Payment: Sending payment success email to: ' . $customer['email']);
            $customerEmailSent = $emailService->sendPaymentSuccessEmail($paymentData);
            
            if ($customerEmailSent) {
                error_log('Verify Payment: Payment confirmation email sent successfully to customer');
            } else {
                error_log('Verify Payment: Failed to send payment confirmation email to customer');
            }
            
            // Send admin new sale notification
            $saleData = [
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'] ?? 'Not provided',
                'plan_name' => $plan['name'],
                'plan_price' => number_format($order['amount'], 2),
                'currency' => $order['currency'] ?? 'INR',
                'order_id' => substr($order['id'], 0, 8),
                'subscription_id' => isset($result['subscription']['id']) ? substr($result['subscription']['id'], 0, 8) : 'N/A',
                'payment_id' => $razorpayPaymentId,
                'payment_method' => 'Online Payment'
            ];
            
            error_log('Verify Payment: Sending new sale notification to admin');
            $adminEmailSent = $emailService->sendNewSaleNotification($saleData);
            
            if ($adminEmailSent) {
                error_log('Verify Payment: New sale notification sent successfully to admin');
            } else {
                error_log('Verify Payment: Failed to send new sale notification to admin');
            }
            
            // Send instance provisioned email if port was allocated
            if ($result['port_allocated'] && isset($result['port']['instance_url'])) {
                error_log('Verify Payment: Sending instance provisioned email');
                
                $myPortUrl = $baseUrl . '/app/my-port.php';
                
                $instanceData = [
                    'customer_name' => $customer['name'],
                    'customer_email' => $customer['email'],
                    'plan_name' => $plan['name'],
                    'instance_url' => $result['port']['instance_url'],
                    'my_port_url' => $myPortUrl
                ];
                
                $instanceEmailSent = $emailService->sendInstanceProvisionedEmail($instanceData);
                
                if ($instanceEmailSent) {
                    error_log('Verify Payment: Instance provisioned email sent successfully');
                } else {
                    error_log('Verify Payment: Failed to send instance provisioned email');
                }
            }
        } else {
            error_log('Verify Payment: Could not send emails - customer or plan not found');
        }
    } catch (Exception $e) {
        error_log('Verify Payment: Email error: ' . $e->getMessage());
        // Don't fail the payment if emails fail
    }
    
    // Log port allocation status
    if ($result['port_allocated']) {
        error_log("Verify Payment: Port allocated - " . ($result['port']['instance_url'] ?? 'N/A'));
    } else {
        error_log("Verify Payment: Port not allocated - " . ($result['port_message'] ?? 'No available ports'));
    }
    
    // Clear session data
    unset($_SESSION['pending_order_id']);
    unset($_SESSION['razorpay_order_id']);
    unset($_SESSION['selected_plan_id']);
    unset($_SESSION['selected_plan_slug']);
    unset($_SESSION['payment_debug']);
    
    // Set success message
    if ($result['port_allocated']) {
        $_SESSION['success'] = 'Payment successful! Your subscription is now active and your instance is ready.';
    } else {
        $_SESSION['success'] = 'Payment successful! Your subscription is being activated. You will be notified when your instance is ready.';
    }
    
    // Redirect to success page
    header('Location: ' . $baseUrl . '/payment-success.php?payment_id=' . urlencode($razorpayPaymentId));
    exit;
    
} catch (Exception $e) {
    error_log('Payment verification error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    $_SESSION['error'] = 'An error occurred during payment verification. Please contact support.';
    header('Location: ' . $baseUrl . '/payment-failed.php');
    exit;
}
