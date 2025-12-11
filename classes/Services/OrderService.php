<?php

namespace Karyalay\Services;

use Karyalay\Models\Order;
use Karyalay\Models\Plan;
use Exception;

/**
 * Order Service
 * 
 * Handles order business logic
 */
class OrderService
{
    private Order $orderModel;
    private Plan $planModel;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->planModel = new Plan();
    }

    /**
     * Create a new order with PENDING status
     * 
     * @param string $customerId Customer ID
     * @param string $planId Plan ID
     * @param string|null $paymentMethod Payment method
     * @return array|false Order data or false on failure
     */
    public function createOrder(string $customerId, string $planId, ?string $paymentMethod = null)
    {
        try {
            // Validate plan exists and is active
            $plan = $this->planModel->findById($planId);
            
            if (!$plan) {
                error_log('Order creation failed: Plan not found - ' . $planId);
                return false;
            }
            
            if ($plan['status'] !== 'ACTIVE') {
                error_log('Order creation failed: Plan not active - ' . $planId);
                return false;
            }
            
            // Calculate effective price: discounted_price if available, otherwise mrp
            $effectivePrice = !empty($plan['discounted_price']) && $plan['discounted_price'] > 0 
                ? $plan['discounted_price'] 
                : $plan['mrp'];
            
            // Create order with PENDING status
            $orderData = [
                'customer_id' => $customerId,
                'plan_id' => $planId,
                'amount' => $effectivePrice,
                'currency' => $plan['currency'],
                'status' => 'PENDING',
                'payment_method' => $paymentMethod
            ];
            
            $order = $this->orderModel->create($orderData);
            
            if (!$order) {
                error_log('Order creation failed for customer: ' . $customerId);
                return false;
            }
            
            return $order;
        } catch (Exception $e) {
            error_log('Order creation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update order status
     * 
     * @param string $orderId Order ID
     * @param string $status New status (PENDING, SUCCESS, FAILED, CANCELLED)
     * @return bool True on success, false on failure
     */
    public function updateOrderStatus(string $orderId, string $status): bool
    {
        try {
            // Validate status
            $validStatuses = ['PENDING', 'SUCCESS', 'FAILED', 'CANCELLED'];
            if (!in_array($status, $validStatuses)) {
                error_log('Invalid order status: ' . $status);
                return false;
            }
            
            // Update order status
            $result = $this->orderModel->updateStatus($orderId, $status);
            
            if ($result) {
                error_log("Order {$orderId} status updated to {$status}");
            } else {
                error_log("Failed to update order {$orderId} status to {$status}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log('Order status update error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order by ID
     * 
     * @param string $orderId Order ID
     * @return array|false Order data or false if not found
     */
    public function getOrder(string $orderId)
    {
        return $this->orderModel->findById($orderId);
    }

    /**
     * Get order by payment gateway order ID
     * 
     * @param string $pgOrderId Payment gateway order ID
     * @return array|false Order data or false if not found
     */
    public function getOrderByPgOrderId(string $pgOrderId)
    {
        return $this->orderModel->findByPgOrderId($pgOrderId);
    }

    /**
     * Get order by payment gateway payment ID
     * 
     * @param string $pgPaymentId Payment gateway payment ID
     * @return array|false Order data or false if not found
     */
    public function getOrderByPgPaymentId(string $pgPaymentId)
    {
        return $this->orderModel->findByPgPaymentId($pgPaymentId);
    }

    /**
     * Legacy method for backward compatibility
     * @deprecated Use getOrderByPgOrderId() instead
     */
    public function getOrderByPaymentGatewayId(string $paymentGatewayId)
    {
        return $this->orderModel->findByPgOrderId($paymentGatewayId);
    }

    /**
     * Get orders by customer ID
     * 
     * @param string $customerId Customer ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Array of orders
     */
    public function getOrdersByCustomer(string $customerId, int $limit = 100, int $offset = 0): array
    {
        return $this->orderModel->findByCustomerId($customerId, $limit, $offset);
    }

    /**
     * Get all orders with optional filters
     * 
     * @param array $filters Optional filters (customer_id, status, plan_id)
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Array of orders
     */
    public function getAllOrders(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        return $this->orderModel->findAll($filters, $limit, $offset);
    }

    /**
     * Update order with payment gateway order ID
     * 
     * @param string $orderId Order ID
     * @param string $pgOrderId Payment gateway order ID
     * @return bool True on success, false on failure
     */
    public function updatePgOrderId(string $orderId, string $pgOrderId): bool
    {
        try {
            return $this->orderModel->update($orderId, [
                'pg_order_id' => $pgOrderId
            ]);
        } catch (Exception $e) {
            error_log('Update PG order ID error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update order with payment gateway payment ID
     * 
     * @param string $orderId Order ID
     * @param string $pgPaymentId Payment gateway payment ID
     * @return bool True on success, false on failure
     */
    public function updatePgPaymentId(string $orderId, string $pgPaymentId): bool
    {
        try {
            return $this->orderModel->update($orderId, [
                'pg_payment_id' => $pgPaymentId
            ]);
        } catch (Exception $e) {
            error_log('Update PG payment ID error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Legacy method for backward compatibility
     * @deprecated Use updatePgOrderId() instead
     */
    public function updatePaymentGatewayId(string $orderId, string $paymentGatewayId): bool
    {
        return $this->updatePgOrderId($orderId, $paymentGatewayId);
    }

    /**
     * Cancel order
     * 
     * @param string $orderId Order ID
     * @return bool True on success, false on failure
     */
    public function cancelOrder(string $orderId): bool
    {
        try {
            $order = $this->orderModel->findById($orderId);
            
            if (!$order) {
                error_log('Cancel order failed: Order not found - ' . $orderId);
                return false;
            }
            
            // Only allow cancellation of PENDING orders
            if ($order['status'] !== 'PENDING') {
                error_log('Cancel order failed: Order not in PENDING status - ' . $orderId);
                return false;
            }
            
            return $this->updateOrderStatus($orderId, 'CANCELLED');
        } catch (Exception $e) {
            error_log('Cancel order error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if order can be processed
     * 
     * @param string $orderId Order ID
     * @return bool True if order can be processed
     */
    public function canProcessOrder(string $orderId): bool
    {
        $order = $this->orderModel->findById($orderId);
        
        if (!$order) {
            return false;
        }
        
        // Order must be in PENDING status to be processed
        return $order['status'] === 'PENDING';
    }

    /**
     * Get order statistics for a customer
     * 
     * @param string $customerId Customer ID
     * @return array Statistics (total_orders, total_spent, successful_orders, failed_orders)
     */
    public function getCustomerOrderStatistics(string $customerId): array
    {
        $orders = $this->orderModel->findByCustomerId($customerId, 1000, 0);
        
        $stats = [
            'total_orders' => count($orders),
            'total_spent' => 0,
            'successful_orders' => 0,
            'failed_orders' => 0,
            'pending_orders' => 0,
            'cancelled_orders' => 0
        ];
        
        foreach ($orders as $order) {
            if ($order['status'] === 'SUCCESS') {
                $stats['successful_orders']++;
                $stats['total_spent'] += $order['amount'];
            } elseif ($order['status'] === 'FAILED') {
                $stats['failed_orders']++;
            } elseif ($order['status'] === 'PENDING') {
                $stats['pending_orders']++;
            } elseif ($order['status'] === 'CANCELLED') {
                $stats['cancelled_orders']++;
            }
        }
        
        return $stats;
    }
}

