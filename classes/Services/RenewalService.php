<?php

namespace Karyalay\Services;

use Karyalay\Models\Subscription;
use Karyalay\Models\Order;
use Karyalay\Models\Plan;
use DateTime;
use Exception;

/**
 * Renewal Service
 * 
 * Handles subscription renewal business logic
 */
class RenewalService
{
    private Subscription $subscriptionModel;
    private Order $orderModel;
    private Plan $planModel;

    public function __construct()
    {
        $this->subscriptionModel = new Subscription();
        $this->orderModel = new Order();
        $this->planModel = new Plan();
    }

    /**
     * Initiate renewal for a subscription
     * 
     * @param string $subscriptionId Subscription ID
     * @return array|false Returns renewal data (order, subscription) or false on failure
     */
    public function initiateRenewal(string $subscriptionId)
    {
        try {
            // Get subscription
            $subscription = $this->subscriptionModel->findById($subscriptionId);
            
            if (!$subscription) {
                error_log('Renewal initiation failed: Subscription not found - ' . $subscriptionId);
                return false;
            }
            
            // Get plan
            $plan = $this->planModel->findById($subscription['plan_id']);
            
            if (!$plan) {
                error_log('Renewal initiation failed: Plan not found - ' . $subscription['plan_id']);
                return false;
            }
            
            if ($plan['status'] !== 'ACTIVE') {
                error_log('Renewal initiation failed: Plan not active - ' . $plan['id']);
                return false;
            }
            
            // Create renewal order with PENDING status
            $orderData = [
                'customer_id' => $subscription['customer_id'],
                'plan_id' => $plan['id'],
                'amount' => !empty($plan['discounted_price']) && $plan['discounted_price'] > 0 ? $plan['discounted_price'] : $plan['mrp'],
                'currency' => $plan['currency'],
                'status' => 'PENDING'
            ];
            
            $order = $this->orderModel->create($orderData);
            
            if (!$order) {
                error_log('Renewal initiation failed: Could not create order for subscription - ' . $subscriptionId);
                return false;
            }
            
            return [
                'order' => $order,
                'subscription' => $subscription,
                'plan' => $plan
            ];
        } catch (Exception $e) {
            error_log('Renewal initiation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process successful renewal payment
     * 
     * @param string $orderId Order ID
     * @param string $subscriptionId Subscription ID to renew
     * @return bool True on success, false on failure
     */
    public function processSuccessfulRenewal(string $orderId, string $subscriptionId): bool
    {
        try {
            // Get order
            $order = $this->orderModel->findById($orderId);
            
            if (!$order) {
                error_log('Renewal processing failed: Order not found - ' . $orderId);
                return false;
            }
            
            // Get subscription
            $subscription = $this->subscriptionModel->findById($subscriptionId);
            
            if (!$subscription) {
                error_log('Renewal processing failed: Subscription not found - ' . $subscriptionId);
                return false;
            }
            
            // Get plan to determine billing period
            $plan = $this->planModel->findById($subscription['plan_id']);
            
            if (!$plan) {
                error_log('Renewal processing failed: Plan not found - ' . $subscription['plan_id']);
                return false;
            }
            
            // Update order status to SUCCESS
            $this->orderModel->updateStatus($orderId, 'SUCCESS');
            
            // Calculate new end date by extending by billing period
            $currentEndDate = new DateTime($subscription['end_date']);
            $newEndDate = clone $currentEndDate;
            $newEndDate->modify("+{$plan['billing_period_months']} months");
            
            // Update subscription end date (maintain existing port assignment)
            $updateResult = $this->subscriptionModel->update($subscriptionId, [
                'end_date' => $newEndDate->format('Y-m-d'),
                'status' => 'ACTIVE' // Ensure status is ACTIVE after renewal
            ]);
            
            if (!$updateResult) {
                error_log('Renewal processing failed: Could not update subscription - ' . $subscriptionId);
                return false;
            }
            
            error_log("Subscription {$subscriptionId} renewed successfully. New end date: " . $newEndDate->format('Y-m-d'));
            
            return true;
        } catch (Exception $e) {
            error_log('Renewal processing error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process failed renewal payment
     * 
     * @param string $orderId Order ID
     * @return bool True on success, false on failure
     */
    public function processFailedRenewal(string $orderId): bool
    {
        try {
            // Get order
            $order = $this->orderModel->findById($orderId);
            
            if (!$order) {
                error_log('Failed renewal processing: Order not found - ' . $orderId);
                return false;
            }
            
            // Update order status to FAILED
            $this->orderModel->updateStatus($orderId, 'FAILED');
            
            // Do not modify subscription - it remains unchanged
            error_log("Renewal payment failed for order {$orderId}. Subscription unchanged.");
            
            return true;
        } catch (Exception $e) {
            error_log('Failed renewal processing error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if subscription is eligible for renewal
     * 
     * @param string $subscriptionId Subscription ID
     * @return bool True if eligible, false otherwise
     */
    public function isEligibleForRenewal(string $subscriptionId): bool
    {
        try {
            $subscription = $this->subscriptionModel->findById($subscriptionId);
            
            if (!$subscription) {
                return false;
            }
            
            // Subscription must be ACTIVE or EXPIRED to be renewed
            return in_array($subscription['status'], ['ACTIVE', 'EXPIRED']);
        } catch (Exception $e) {
            error_log('Renewal eligibility check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get renewal details for a subscription
     * 
     * @param string $subscriptionId Subscription ID
     * @return array|false Returns renewal details or false on failure
     */
    public function getRenewalDetails(string $subscriptionId)
    {
        try {
            $subscription = $this->subscriptionModel->findById($subscriptionId);
            
            if (!$subscription) {
                return false;
            }
            
            $plan = $this->planModel->findById($subscription['plan_id']);
            
            if (!$plan) {
                return false;
            }
            
            // Calculate new end date
            $currentEndDate = new DateTime($subscription['end_date']);
            $newEndDate = clone $currentEndDate;
            $newEndDate->modify("+{$plan['billing_period_months']} months");
            
            return [
                'subscription' => $subscription,
                'plan' => $plan,
                'current_end_date' => $subscription['end_date'],
                'new_end_date' => $newEndDate->format('Y-m-d'),
                'renewal_amount' => !empty($plan['discounted_price']) && $plan['discounted_price'] > 0 ? $plan['discounted_price'] : $plan['mrp'],
                'currency' => $plan['currency'],
                'billing_period_months' => $plan['billing_period_months']
            ];
        } catch (Exception $e) {
            error_log('Get renewal details error: ' . $e->getMessage());
            return false;
        }
    }
}

