<?php

namespace Karyalay\Services;

use Karyalay\Models\Subscription;
use Exception;

/**
 * Expiration Service
 * 
 * Handles subscription expiration business logic
 */
class ExpirationService
{
    private Subscription $subscriptionModel;

    public function __construct()
    {
        $this->subscriptionModel = new Subscription();
    }

    /**
     * Process expired subscriptions
     * Updates all active subscriptions that have passed their end date to EXPIRED status
     * 
     * @return array Returns array with count of expired subscriptions and their IDs
     */
    public function processExpiredSubscriptions(): array
    {
        try {
            // Find all active subscriptions that have passed their end date
            $expiredSubscriptions = $this->subscriptionModel->findExpired();
            
            $expiredCount = 0;
            $expiredIds = [];
            
            foreach ($expiredSubscriptions as $subscription) {
                // Update status to EXPIRED
                $result = $this->subscriptionModel->updateStatus($subscription['id'], 'EXPIRED');
                
                if ($result) {
                    $expiredCount++;
                    $expiredIds[] = $subscription['id'];
                    error_log("Subscription {$subscription['id']} expired. End date was: {$subscription['end_date']}");
                } else {
                    error_log("Failed to expire subscription {$subscription['id']}");
                }
            }
            
            return [
                'count' => $expiredCount,
                'subscription_ids' => $expiredIds
            ];
        } catch (Exception $e) {
            error_log('Expiration processing error: ' . $e->getMessage());
            return [
                'count' => 0,
                'subscription_ids' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if a specific subscription should be expired
     * 
     * @param string $subscriptionId Subscription ID
     * @return bool True if subscription should be expired, false otherwise
     */
    public function shouldExpire(string $subscriptionId): bool
    {
        try {
            $subscription = $this->subscriptionModel->findById($subscriptionId);
            
            if (!$subscription) {
                return false;
            }
            
            // Only ACTIVE subscriptions can be expired
            if ($subscription['status'] !== 'ACTIVE') {
                return false;
            }
            
            // Check if end date has passed
            $endDate = new \DateTime($subscription['end_date']);
            $now = new \DateTime();
            
            return $endDate < $now;
        } catch (Exception $e) {
            error_log('Should expire check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Expire a specific subscription
     * 
     * @param string $subscriptionId Subscription ID
     * @return bool True on success, false on failure
     */
    public function expireSubscription(string $subscriptionId): bool
    {
        try {
            if (!$this->shouldExpire($subscriptionId)) {
                return false;
            }
            
            return $this->subscriptionModel->updateStatus($subscriptionId, 'EXPIRED');
        } catch (Exception $e) {
            error_log('Expire subscription error: ' . $e->getMessage());
            return false;
        }
    }
}
