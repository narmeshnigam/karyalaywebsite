<?php

namespace Karyalay\Services;

use Karyalay\Models\Port;
use Karyalay\Models\PortAllocationLog;
use Karyalay\Models\Subscription;
use Karyalay\Models\Order;
use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * Port Allocation Service
 * 
 * Handles atomic port allocation to subscriptions with transaction safety
 */
class PortAllocationService
{
    private Port $portModel;
    private PortAllocationLog $logModel;
    private Subscription $subscriptionModel;
    private Order $orderModel;
    private PDO $db;

    public function __construct()
    {
        $this->portModel = new Port();
        $this->logModel = new PortAllocationLog();
        $this->subscriptionModel = new Subscription();
        $this->orderModel = new Order();
        $this->db = Connection::getInstance();
    }

    /**
     * Allocate port to a subscription
     * 
     * This method implements atomic port assignment with database transaction.
     * It queries for available ports matching plan criteria, assigns the first one,
     * updates port status to ASSIGNED, records assignment timestamp, and links
     * port to subscription and customer.
     * 
     * @param string $subscriptionId Subscription ID
     * @return array Returns array with 'success' boolean and 'port' or 'error'
     */
    public function allocatePortToSubscription(string $subscriptionId): array
    {
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Get subscription
            $subscription = $this->subscriptionModel->findById($subscriptionId);
            if (!$subscription) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'Subscription not found'
                ];
            }

            // Check if subscription already has a port
            if ($subscription['assigned_port_id']) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'Subscription already has an assigned port'
                ];
            }

            // Query for available ports matching plan criteria
            $availablePorts = $this->portModel->findAvailableByPlanId($subscription['plan_id'], 1);

            if (empty($availablePorts)) {
                // Mark subscription as PENDING_ALLOCATION
                $this->subscriptionModel->updateStatus($subscriptionId, 'PENDING_ALLOCATION');
                
                // TODO: Send notification to admin
                // This would typically use an EmailService or NotificationService
                error_log("ADMIN NOTIFICATION: No available ports for subscription {$subscriptionId} on plan {$subscription['plan_id']}");
                
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'NO_AVAILABLE_PORTS',
                    'message' => 'No available ports for this plan. Subscription marked as PENDING_ALLOCATION.'
                ];
            }

            $port = $availablePorts[0];
            $assignedAt = date('Y-m-d H:i:s');

            // Assign port to subscription (atomic operation)
            $assignSuccess = $this->portModel->assignToSubscription(
                $port['id'],
                $subscriptionId,
                $subscription['customer_id'],
                $assignedAt
            );

            if (!$assignSuccess) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'Failed to assign port'
                ];
            }

            // Link port to subscription
            $linkSuccess = $this->subscriptionModel->assignPort($subscriptionId, $port['id']);

            if (!$linkSuccess) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'Failed to link port to subscription'
                ];
            }

            // Log the assignment
            $this->logModel->logAssignment(
                $port['id'],
                $subscriptionId,
                $subscription['customer_id'],
                null // null for automatic assignment
            );

            // Commit transaction
            $this->db->commit();

            // Get updated port
            $updatedPort = $this->portModel->findById($port['id']);

            return [
                'success' => true,
                'port' => $updatedPort,
                'subscription_id' => $subscriptionId
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('PortAllocationService::allocatePortToSubscription failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred during port allocation'
            ];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('PortAllocationService::allocatePortToSubscription failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred during port allocation'
            ];
        }
    }

    /**
     * Allocate port for an order
     * 
     * This is typically called after successful payment.
     * It finds the subscription associated with the order and allocates a port.
     * 
     * @param string $orderId Order ID
     * @return array Returns array with 'success' boolean and 'port' or 'error'
     */
    public function allocatePortForOrder(string $orderId): array
    {
        try {
            // Get order
            $order = $this->orderModel->findById($orderId);
            if (!$order) {
                return [
                    'success' => false,
                    'error' => 'Order not found'
                ];
            }

            // Check order status
            if ($order['status'] !== 'SUCCESS') {
                return [
                    'success' => false,
                    'error' => 'Order status must be SUCCESS for port allocation'
                ];
            }

            // Find subscription for this order
            $subscription = $this->subscriptionModel->findByOrderId($orderId);
            if (!$subscription) {
                return [
                    'success' => false,
                    'error' => 'No subscription found for this order'
                ];
            }

            // Allocate port to subscription
            return $this->allocatePortToSubscription($subscription['id']);
        } catch (\Exception $e) {
            error_log('PortAllocationService::allocatePortForOrder failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while allocating port for order'
            ];
        }
    }

    /**
     * Reassign port from one subscription to another
     * 
     * This is an admin function to manually reassign ports.
     * Logs the reassignment action with timestamp and admin ID.
     * 
     * @param string $portId Port ID
     * @param string $newSubscriptionId New subscription ID
     * @param string $adminId Admin user ID performing the reassignment
     * @return array Returns array with 'success' boolean and optional 'error'
     */
    public function reassignPort(string $portId, string $newSubscriptionId, string $adminId): array
    {
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Get port
            $port = $this->portModel->findById($portId);
            if (!$port) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'Port not found'
                ];
            }

            // Get old subscription if exists
            $oldSubscriptionId = $port['assigned_subscription_id'];

            // Get new subscription
            $newSubscription = $this->subscriptionModel->findById($newSubscriptionId);
            if (!$newSubscription) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'New subscription not found'
                ];
            }

            // Check if new subscription already has a port
            if ($newSubscription['assigned_port_id']) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'New subscription already has an assigned port'
                ];
            }

            $assignedAt = date('Y-m-d H:i:s');

            // Reassign port
            $assignSuccess = $this->portModel->assignToSubscription(
                $portId,
                $newSubscriptionId,
                $newSubscription['customer_id'],
                $assignedAt
            );

            if (!$assignSuccess) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'Failed to reassign port'
                ];
            }

            // Update old subscription if exists
            if ($oldSubscriptionId) {
                $this->subscriptionModel->update($oldSubscriptionId, ['assigned_port_id' => null]);
            }

            // Link port to new subscription
            $linkSuccess = $this->subscriptionModel->assignPort($newSubscriptionId, $portId);

            if (!$linkSuccess) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'Failed to link port to new subscription'
                ];
            }

            // Log the reassignment
            $this->logModel->logReassignment(
                $portId,
                $newSubscriptionId,
                $newSubscription['customer_id'],
                $adminId
            );

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'port_id' => $portId,
                'old_subscription_id' => $oldSubscriptionId,
                'new_subscription_id' => $newSubscriptionId
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('PortAllocationService::reassignPort failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred during port reassignment'
            ];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('PortAllocationService::reassignPort failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred during port reassignment'
            ];
        }
    }

    /**
     * Release port from subscription
     * 
     * @param string $portId Port ID
     * @param string|null $performedBy Admin user ID (null for automatic release)
     * @return array Returns array with 'success' boolean and optional 'error'
     */
    public function releasePort(string $portId, ?string $performedBy = null): array
    {
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Get port
            $port = $this->portModel->findById($portId);
            if (!$port) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'Port not found'
                ];
            }

            $oldSubscriptionId = $port['assigned_subscription_id'];
            $oldCustomerId = $port['assigned_customer_id'];

            // Release port
            $releaseSuccess = $this->portModel->release($portId);

            if (!$releaseSuccess) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error' => 'Failed to release port'
                ];
            }

            // Update subscription if exists
            if ($oldSubscriptionId) {
                $this->subscriptionModel->update($oldSubscriptionId, ['assigned_port_id' => null]);

                // Log the release
                $this->logModel->logRelease(
                    $portId,
                    $oldSubscriptionId,
                    $oldCustomerId,
                    $performedBy
                );
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'port_id' => $portId
            ];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('PortAllocationService::releasePort failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error occurred during port release'
            ];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('PortAllocationService::releasePort failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred during port release'
            ];
        }
    }

    /**
     * Check if ports are available for a plan
     * 
     * @param string $planId Plan ID
     * @return bool Returns true if at least one port is available
     */
    public function hasAvailablePorts(string $planId): bool
    {
        try {
            $count = $this->portModel->countAvailableByPlanId($planId);
            return $count > 0;
        } catch (\Exception $e) {
            error_log('PortAllocationService::hasAvailablePorts failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get available ports count for a plan
     * 
     * @param string $planId Plan ID
     * @return int Returns count of available ports
     */
    public function getAvailablePortsCount(string $planId): int
    {
        try {
            return $this->portModel->countAvailableByPlanId($planId);
        } catch (\Exception $e) {
            error_log('PortAllocationService::getAvailablePortsCount failed: ' . $e->getMessage());
            return 0;
        }
    }
}
