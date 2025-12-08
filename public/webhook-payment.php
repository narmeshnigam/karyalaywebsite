<?php

/**
 * Karyalay Portal System
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
    $subscriptionModel = new Subscription();
    $portModel = new Port();
    
    // Create subscription
    $subscriptionData = [
        'customer_id' => $order['customer_id'],
        'plan_id' => $order['plan_id'],
        'order_id' => $order['id'],
        'status' => 'ACTIVE'
    ];
    
    $subscription = $subscriptionModel->create($subscriptionData);
    
    if (!$subscription) {
        error_log('Failed to create subscription for order: ' . $order['id']);
        return;
    }
    
    error_log('Subscription created: ' . $subscription['id']);
    
    // Allocate port
    $availablePort = $portModel->findAvailableForPlan($order['plan_id']);
    
    if ($availablePort) {
        // Assign port to subscription
        $portModel->assignToSubscription(
            $availablePort['id'],
            $subscription['id'],
            $order['customer_id'],
            date('Y-m-d H:i:s')
        );
        
        // Update subscription with assigned port
        $subscriptionModel->update($subscription['id'], [
            'assigned_port_id' => $availablePort['id']
        ]);
        
        error_log('Port allocated: ' . $availablePort['id'] . ' to subscription: ' . $subscription['id']);
    } else {
        // No available ports - mark subscription as pending allocation
        $subscriptionModel->updateStatus($subscription['id'], 'PENDING_ALLOCATION');
        error_log('No available ports for plan: ' . $order['plan_id']);
        
        // Send notification to admin
        try {
            $emailService = new EmailService();
            $config = require __DIR__ . '/../config/app.php';
            $emailService->sendEmail(
                $config['admin_email'],
                'Port Allocation Required',
                "Subscription {$subscription['id']} requires port allocation. No available ports for plan {$order['plan_id']}."
            );
        } catch (Exception $e) {
            error_log('Failed to send admin notification: ' . $e->getMessage());
        }
    }
    
    // Send confirmation email to customer
    try {
        $emailService = new EmailService();
        // TODO: Fetch customer email and send confirmation
        error_log('Payment confirmation email should be sent to customer');
    } catch (Exception $e) {
        error_log('Failed to send customer confirmation: ' . $e->getMessage());
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

