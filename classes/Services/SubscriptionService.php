<?php

namespace Karyalay\Services;

use Karyalay\Models\Subscription;
use Karyalay\Models\Port;
use Karyalay\Models\Order;
use Karyalay\Models\Plan;
use Exception;

/**
 * Subscription Service
 * 
 * Handles subscription creation, port allocation, and management
 */
class SubscriptionService
{
    private Subscription $subscriptionModel;
    private Port $portModel;
    private Order $orderModel;
    private Plan $planModel;

    public function __construct()
    {
        $this->subscriptionModel = new Subscription();
        $this->portModel = new Port();
        $this->orderModel = new Order();
        $this->planModel = new Plan();
    }

    /**
     * Process a successful payment and create subscription with port allocation
     * 
     * @param string $orderId Order ID
     * @param string $razorpayPaymentId Razorpay payment ID for reference
     * @return array Result with success status and subscription/port details
     */
    public function processSuccessfulPayment(string $orderId, string $razorpayPaymentId = ''): array
    {
        try {
            // Get order details
            $order = $this->orderModel->findById($orderId);
            
            if (!$order) {
                error_log("SubscriptionService: Order not found - {$orderId}");
                return [
                    'success' => false,
                    'error' => 'Order not found'
                ];
            }
            
            // Check if order is already processed (has a subscription)
            $existingSubscription = $this->subscriptionModel->findByOrderId($orderId);
            if ($existingSubscription) {
                error_log("SubscriptionService: Order already processed - {$orderId}");
                return [
                    'success' => true,
                    'message' => 'Order already processed',
                    'subscription' => $existingSubscription,
                    'already_processed' => true
                ];
            }
            
            // Update order status to SUCCESS if not already
            if ($order['status'] !== 'SUCCESS') {
                $this->orderModel->updateStatus($orderId, 'SUCCESS');
                error_log("SubscriptionService: Order status updated to SUCCESS - {$orderId}");
            }
            
            // Generate and store invoice_id for successful payment
            $invoiceService = new InvoiceService();
            $invoiceId = $invoiceService->createInvoiceId($orderId);
            if ($invoiceId) {
                error_log("SubscriptionService: Invoice ID generated - {$invoiceId}");
            }
            
            // Create subscription
            $subscriptionData = [
                'customer_id' => $order['customer_id'],
                'plan_id' => $order['plan_id'],
                'order_id' => $orderId,
                'status' => 'ACTIVE'
            ];
            
            $subscription = $this->subscriptionModel->create($subscriptionData);
            
            if (!$subscription) {
                error_log("SubscriptionService: Failed to create subscription for order - {$orderId}");
                return [
                    'success' => false,
                    'error' => 'Failed to create subscription'
                ];
            }
            
            error_log("SubscriptionService: Subscription created - {$subscription['id']}");
            
            // Allocate port
            $portResult = $this->allocatePortToSubscription($subscription['id'], $order['plan_id'], $order['customer_id']);
            
            return [
                'success' => true,
                'subscription' => $subscription,
                'port_allocated' => $portResult['success'],
                'port' => $portResult['port'] ?? null,
                'port_message' => $portResult['message'] ?? null
            ];
            
        } catch (Exception $e) {
            error_log("SubscriptionService: Error processing payment - " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Allocate an available port to a subscription
     * 
     * @param string $subscriptionId Subscription ID
     * @param string $planId Plan ID (kept for backward compatibility, not used for port selection)
     * @param string $customerId Customer ID
     * @return array Result with success status and port details
     */
    public function allocatePortToSubscription(string $subscriptionId, string $planId, string $customerId): array
    {
        try {
            // Find any available port (plan-agnostic)
            $availablePorts = $this->portModel->findAvailable(1);
            
            if (empty($availablePorts)) {
                // No available ports - mark subscription as pending allocation
                $this->subscriptionModel->updateStatus($subscriptionId, 'PENDING_ALLOCATION');
                error_log("SubscriptionService: No available ports for subscription - {$subscriptionId}");
                
                return [
                    'success' => false,
                    'message' => 'No available ports. Your instance will be allocated soon.'
                ];
            }
            
            $port = $availablePorts[0];
            $assignedAt = date('Y-m-d H:i:s');
            
            // Assign port to subscription
            $portAssigned = $this->portModel->assignToSubscription(
                $port['id'],
                $subscriptionId,
                $assignedAt
            );
            
            // Log the port allocation
            $logModel = new \Karyalay\Models\PortAllocationLog();
            $logModel->logAssignment($port['id'], $subscriptionId, $customerId, null);
            
            if (!$portAssigned) {
                error_log("SubscriptionService: Failed to assign port {$port['id']} to subscription {$subscriptionId}");
                return [
                    'success' => false,
                    'message' => 'Failed to assign port'
                ];
            }
            
            // Update subscription with assigned port
            $this->subscriptionModel->assignPort($subscriptionId, $port['id']);
            
            error_log("SubscriptionService: Port {$port['id']} allocated to subscription {$subscriptionId}");
            
            // Refresh port data
            $updatedPort = $this->portModel->findById($port['id']);
            
            return [
                'success' => true,
                'port' => $updatedPort,
                'message' => 'Port allocated successfully'
            ];
            
        } catch (Exception $e) {
            error_log("SubscriptionService: Error allocating port - " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get subscription with full details (plan, port)
     * 
     * @param string $subscriptionId Subscription ID
     * @return array|null Subscription with details or null
     */
    public function getSubscriptionWithDetails(string $subscriptionId): ?array
    {
        $subscription = $this->subscriptionModel->findById($subscriptionId);
        
        if (!$subscription) {
            return null;
        }
        
        // Get plan details
        $plan = $this->planModel->findById($subscription['plan_id']);
        
        // Get port details if assigned
        $port = null;
        if ($subscription['assigned_port_id']) {
            $port = $this->portModel->findById($subscription['assigned_port_id']);
        }
        
        return [
            'subscription' => $subscription,
            'plan' => $plan,
            'port' => $port
        ];
    }

    /**
     * Get active subscription for customer with full details
     * 
     * @param string $customerId Customer ID
     * @return array|null Subscription with details or null
     */
    public function getActiveSubscriptionForCustomer(string $customerId): ?array
    {
        $subscription = $this->subscriptionModel->findActiveByCustomerId($customerId);
        
        if (!$subscription) {
            return null;
        }
        
        return $this->getSubscriptionWithDetails($subscription['id']);
    }

    /**
     * Release port from subscription (for cancellation/expiry)
     * 
     * @param string $subscriptionId Subscription ID
     * @return bool Success status
     */
    public function releasePortFromSubscription(string $subscriptionId): bool
    {
        try {
            $subscription = $this->subscriptionModel->findById($subscriptionId);
            
            if (!$subscription || !$subscription['assigned_port_id']) {
                return true; // Nothing to release
            }
            
            // Release the port
            $released = $this->portModel->release($subscription['assigned_port_id']);
            
            if ($released) {
                // Clear port from subscription
                $this->subscriptionModel->update($subscriptionId, ['assigned_port_id' => null]);
                error_log("SubscriptionService: Port released from subscription {$subscriptionId}");
            }
            
            return $released;
            
        } catch (Exception $e) {
            error_log("SubscriptionService: Error releasing port - " . $e->getMessage());
            return false;
        }
    }
}
