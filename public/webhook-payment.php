<?php

/**
 * SellerPortal System
 * Payment Webhook Handler
 * 
 * Handles payment confirmation webhooks from Razorpay
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Never display errors in webhook

use Karyalay\Models\Order;
use Karyalay\Models\Subscription;
use Karyalay\Models\Port;
use Karyalay\Services\PaymentService;
use Karyalay\Services\EmailService;

// Log webhook received
error_log('Payment webhook received');

try {
    // Get webhook payload
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';
    
    if (empty($payload) || empty($signature)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid webhook request']);
        exit;
    }
    
    // Verify webhook signature
    $paymentService = new PaymentService();
    if (!$paymentService->verifyWebhookSignature($payload, $signature)) {
        error_log('Webhook signature verification failed');
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
    
    // Parse webhook data
    $webhookData = json_decode($payload, true);
    
    if (!$webhookData || !isset($webhookData['event'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid webhook data']);
        exit;
    }
    
    $event = $webhookData['event'];
    $paymentEntity = $webhookData['payload']['payment']['entity'] ?? null;
    
    error_log('Webhook event: ' . $event);
    
    // Handle payment.authorized or payment.captured events
    if (($event === 'payment.authorized' || $event === 'payment.captured') && $paymentEntity) {
        handlePaymentSuccess($paymentEntity);
    }
    // Handle payment.failed event
    elseif ($event === 'payment.failed' && $paymentEntity) {
        handlePaymentFailure($paymentEntity);
    }
    
    // Respond with success
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log('Webhook processing error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Handle successful payment
 */
function handlePaymentSuccess(array $paymentEntity): void
{
    $orderModel = new Order();
    $subscriptionModel = new Subscription();
    $portModel = new Port();
    
    // Find order by payment gateway order ID
    $razorpayOrderId = $paymentEntity['order_id'] ?? null;
    if (!$razorpayOrderId) {
        error_log('No order_id in payment entity');
        return;
    }
    
    $order = $orderModel->findByPaymentGatewayId($razorpayOrderId);
    if (!$order) {
        error_log('Order not found for payment gateway ID: ' . $razorpayOrderId);
        return;
    }
    
    // Check if order is already processed
    if ($order['status'] === 'SUCCESS') {
        error_log('Order already processed: ' . $order['id']);
        return;
    }
    
    // Check if this is a renewal payment
    // Renewal orders will have metadata or notes indicating the subscription_id
    $isRenewal = false;
    $renewalSubscriptionId = null;
    
    // Check order notes or metadata for renewal information
    // This would be set during order creation in process-payment.php
    if (isset($paymentEntity['notes']) && isset($paymentEntity['notes']['subscription_id'])) {
        $isRenewal = true;
        $renewalSubscriptionId = $paymentEntity['notes']['subscription_id'];
    }
    
    // Update order status to SUCCESS
    $orderModel->updateStatus($order['id'], 'SUCCESS');
    error_log('Order status updated to SUCCESS: ' . $order['id']);
    
    if ($isRenewal && $renewalSubscriptionId) {
        // Handle renewal payment
        handleRenewalPaymentSuccess($order['id'], $renewalSubscriptionId);
    } else {
        // Handle new subscription payment
        handleNewSubscriptionPayment($order);
    }
}

/**
 * Handle new subscription payment
 */
function handleNewSubscriptionPayment(array $order): void
{
    // Use SubscriptionService for consistent handling
    $subscriptionService = new \Karyalay\Services\SubscriptionService();
    
    $result = $subscriptionService->processSuccessfulPayment($order['id']);
    
    if (!$result['success']) {
        error_log('Webhook: Failed to process subscription for order: ' . $order['id'] . ' - ' . ($result['error'] ?? 'Unknown error'));
        return;
    }
    
    // Check if this was already processed (idempotency)
    if (isset($result['already_processed']) && $result['already_processed']) {
        error_log('Webhook: Order already processed: ' . $order['id']);
        return;
    }
    
    error_log('Webhook: Subscription created: ' . $result['subscription']['id']);
    
    if ($result['port_allocated']) {
        error_log('Webhook: Port allocated to subscription: ' . $result['subscription']['id']);
    } else {
        error_log('Webhook: Port not allocated - ' . ($result['port_message'] ?? 'No available ports'));
        
        // Send notification to admin about pending port allocation
        try {
            $emailService = new EmailService();
            $config = require __DIR__ . '/../config/app.php';
            if (!empty($config['admin_email'])) {
                $emailService->sendEmail(
                    $config['admin_email'],
                    'Port Allocation Required',
                    "Subscription {$result['subscription']['id']} requires port allocation. No available ports for plan {$order['plan_id']}."
                );
            }
        } catch (Exception $e) {
            error_log('Webhook: Failed to send admin notification: ' . $e->getMessage());
        }
    }
    
    // Send confirmation emails
    try {
        error_log('Webhook: Starting email notification process');
        
        $emailService = new EmailService();
        $userModel = new \Karyalay\Models\User();
        $planModel = new \Karyalay\Models\Plan();
        
        error_log('Webhook: Fetching customer ID: ' . $order['customer_id']);
        error_log('Webhook: Fetching plan ID: ' . $order['plan_id']);
        
        // Fetch customer details
        $customer = $userModel->findById($order['customer_id']);
        $plan = $planModel->findById($order['plan_id']);
        
        if (!$customer) {
            error_log('Webhook: Customer not found for ID: ' . $order['customer_id']);
            return;
        }
        
        if (!$plan) {
            error_log('Webhook: Plan not found for ID: ' . $order['plan_id']);
            return;
        }
        
        error_log('Webhook: Customer found: ' . $customer['email']);
        error_log('Webhook: Plan found: ' . $plan['name']);
        
        // Generate invoice URL
        $invoiceUrl = ($_ENV['APP_URL'] ?? 'http://localhost') . '/app/billing/invoice.php?id=' . $order['id'];
        
        // Send payment success email to customer
        error_log('Webhook: Preparing payment success email data');
        $paymentData = [
            'customer_name' => $customer['name'],
            'customer_email' => $customer['email'],
            'plan_name' => $plan['name'],
            'amount' => number_format($order['amount'], 2),
            'currency' => $order['currency'] ?? 'USD',
            'order_id' => substr($order['id'], 0, 8),
            'payment_id' => $order['payment_gateway_payment_id'] ?? 'N/A',
            'invoice_url' => $invoiceUrl
        ];
        
        error_log('Webhook: Sending payment success email to: ' . $customer['email']);
        $customerEmailSent = $emailService->sendPaymentSuccessEmail($paymentData);
        
        if ($customerEmailSent) {
            error_log('Webhook: Payment confirmation email sent successfully to customer');
        } else {
            error_log('Webhook: Failed to send payment confirmation email to customer');
        }
        
        // Send new sale notification to admin
        error_log('Webhook: Preparing admin sale notification data');
        $saleData = [
            'customer_name' => $customer['name'],
            'customer_email' => $customer['email'],
            'customer_phone' => $customer['phone'] ?? 'Not provided',
            'plan_name' => $plan['name'],
            'plan_price' => number_format($order['amount'], 2),
            'currency' => $order['currency'] ?? 'USD',
            'order_id' => substr($order['id'], 0, 8),
            'subscription_id' => substr($result['subscription']['id'], 0, 8),
            'payment_id' => $order['payment_gateway_payment_id'] ?? 'N/A',
            'payment_method' => 'Online Payment'
        ];
        
        error_log('Webhook: Sending new sale notification to admin');
        $adminEmailSent = $emailService->sendNewSaleNotification($saleData);
        
        if ($adminEmailSent) {
            error_log('Webhook: New sale notification sent successfully to admin');
        } else {
            error_log('Webhook: Failed to send new sale notification to admin');
        }
        
        // Send instance provisioned email if port was allocated
        if ($result['port_allocated'] && isset($result['port']['instance_url'])) {
            error_log('Webhook: Sending instance provisioned email');
            
            $myPortUrl = ($_ENV['APP_URL'] ?? 'http://localhost') . '/app/my-port.php';
            
            $instanceData = [
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'plan_name' => $plan['name'],
                'instance_url' => $result['port']['instance_url'],
                'my_port_url' => $myPortUrl
            ];
            
            $instanceEmailSent = $emailService->sendInstanceProvisionedEmail($instanceData);
            
            if ($instanceEmailSent) {
                error_log('Webhook: Instance provisioned email sent successfully');
            } else {
                error_log('Webhook: Failed to send instance provisioned email');
            }
        }
        
    } catch (Exception $e) {
        error_log('Webhook: Exception while sending confirmation emails: ' . $e->getMessage());
        error_log('Webhook: Stack trace: ' . $e->getTraceAsString());
    }
}

/**
 * Handle renewal payment success
 */
function handleRenewalPaymentSuccess(string $orderId, string $subscriptionId): void
{
    $renewalService = new \Karyalay\Services\RenewalService();
    
    // Process successful renewal
    $success = $renewalService->processSuccessfulRenewal($orderId, $subscriptionId);
    
    if ($success) {
        error_log("Renewal processed successfully for subscription: {$subscriptionId}, order: {$orderId}");
        
        // Send renewal confirmation email to customer
        try {
            $emailService = new EmailService();
            // TODO: Fetch customer email and send renewal confirmation
            error_log('Renewal confirmation email should be sent to customer');
        } catch (Exception $e) {
            error_log('Failed to send renewal confirmation: ' . $e->getMessage());
        }
    } else {
        error_log("Failed to process renewal for subscription: {$subscriptionId}, order: {$orderId}");
    }
}

/**
 * Handle failed payment
 */
function handlePaymentFailure(array $paymentEntity): void
{
    $orderModel = new Order();
    
    // Find order by payment gateway order ID
    $razorpayOrderId = $paymentEntity['order_id'] ?? null;
    if (!$razorpayOrderId) {
        error_log('No order_id in payment entity');
        return;
    }
    
    $order = $orderModel->findByPaymentGatewayId($razorpayOrderId);
    if (!$order) {
        error_log('Order not found for payment gateway ID: ' . $razorpayOrderId);
        return;
    }
    
    // Check if this is a renewal payment
    $isRenewal = false;
    if (isset($paymentEntity['notes']) && isset($paymentEntity['notes']['subscription_id'])) {
        $isRenewal = true;
        $renewalSubscriptionId = $paymentEntity['notes']['subscription_id'];
    }
    
    // Update order status to FAILED
    $orderModel->updateStatus($order['id'], 'FAILED');
    error_log('Order status updated to FAILED: ' . $order['id']);
    
    if ($isRenewal && isset($renewalSubscriptionId)) {
        // Handle failed renewal payment
        $renewalService = new \Karyalay\Services\RenewalService();
        $renewalService->processFailedRenewal($order['id']);
        error_log("Failed renewal payment for subscription: {$renewalSubscriptionId}, order: {$order['id']}");
    }
    
    // Do not create subscription or allocate port for new subscriptions
    // Do not modify subscription for renewals
}

