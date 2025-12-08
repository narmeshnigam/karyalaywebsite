<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;
use DateTime;

/**
 * Subscription Model Class
 * 
 * Handles CRUD operations for subscriptions table
 */
class Subscription
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new subscription
     * 
     * @param array $data Subscription data (customer_id, plan_id, start_date, end_date, status, assigned_port_id, order_id)
     * @return array|false Returns subscription data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();
            
            // Calculate start_date and end_date if not provided
            if (!isset($data['start_date'])) {
                $data['start_date'] = date('Y-m-d');
            }
            
            if (!isset($data['end_date']) && isset($data['plan_id'])) {
                // Fetch plan to get billing period
                $planModel = new Plan();
                $plan = $planModel->findById($data['plan_id']);
                
                if ($plan && isset($plan['billing_period_months'])) {
                    $startDate = new DateTime($data['start_date']);
                    $endDate = clone $startDate;
                    $endDate->modify("+{$plan['billing_period_months']} months");
                    $data['end_date'] = $endDate->format('Y-m-d');
                }
            }

            $sql = "INSERT INTO subscriptions (
                id, customer_id, plan_id, start_date, end_date, status, assigned_port_id, order_id
            ) VALUES (
                :id, :customer_id, :plan_id, :start_date, :end_date, :status, :assigned_port_id, :order_id
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':customer_id' => $data['customer_id'],
                ':plan_id' => $data['plan_id'],
                ':start_date' => $data['start_date'],
                ':end_date' => $data['end_date'],
                ':status' => $data['status'] ?? 'ACTIVE',
                ':assigned_port_id' => $data['assigned_port_id'] ?? null,
                ':order_id' => $data['order_id']
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Subscription creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find subscription by ID
     * 
     * @param string $id Subscription ID
     * @return array|false Returns subscription data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM subscriptions WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Subscription find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find subscriptions by customer ID
     * 
     * @param string $customerId Customer ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of subscriptions
     */
    public function findByCustomerId(string $customerId, int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM subscriptions WHERE customer_id = :customer_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Subscription find by customer ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find subscription by order ID
     * 
     * @param string $orderId Order ID
     * @return array|false Returns subscription data or false if not found
     */
    public function findByOrderId(string $orderId)
    {
        try {
            $sql = "SELECT * FROM subscriptions WHERE order_id = :order_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':order_id' => $orderId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Subscription find by order ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find active subscription for customer
     * 
     * @param string $customerId Customer ID
     * @return array|false Returns active subscription or false if not found
     */
    public function findActiveByCustomerId(string $customerId)
    {
        try {
            $sql = "SELECT * FROM subscriptions WHERE customer_id = :customer_id AND status = 'ACTIVE' ORDER BY end_date DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':customer_id' => $customerId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Active subscription find failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update subscription data
     * 
     * @param string $id Subscription ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['start_date', 'end_date', 'status', 'assigned_port_id', 'plan_id'];
            $updateFields = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $updateFields[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }

            if (empty($updateFields)) {
                return false;
            }

            $sql = "UPDATE subscriptions SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Subscription update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete subscription
     * 
     * @param string $id Subscription ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM subscriptions WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Subscription deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all subscriptions with optional filters
     * 
     * @param array $filters Optional filters (customer_id, status, plan_id, end_date_from, end_date_to)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of subscriptions
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM subscriptions WHERE 1=1";
            $params = [];

            if (isset($filters['customer_id'])) {
                $sql .= " AND customer_id = :customer_id";
                $params[':customer_id'] = $filters['customer_id'];
            }

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['plan_id'])) {
                $sql .= " AND plan_id = :plan_id";
                $params[':plan_id'] = $filters['plan_id'];
            }

            if (isset($filters['end_date_from'])) {
                $sql .= " AND end_date >= :end_date_from";
                $params[':end_date_from'] = $filters['end_date_from'];
            }

            if (isset($filters['end_date_to'])) {
                $sql .= " AND end_date <= :end_date_to";
                $params[':end_date_to'] = $filters['end_date_to'];
            }

            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Subscription findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update subscription status
     * 
     * @param string $id Subscription ID
     * @param string $status New status (ACTIVE, EXPIRED, CANCELLED, PENDING_ALLOCATION)
     * @return bool Returns true on success, false on failure
     */
    public function updateStatus(string $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Extend subscription end date
     * 
     * @param string $id Subscription ID
     * @param int $months Number of months to extend
     * @return bool Returns true on success, false on failure
     */
    public function extendEndDate(string $id, int $months): bool
    {
        try {
            $subscription = $this->findById($id);
            if (!$subscription) {
                return false;
            }

            $endDate = new DateTime($subscription['end_date']);
            $endDate->modify("+{$months} months");
            
            return $this->update($id, ['end_date' => $endDate->format('Y-m-d')]);
        } catch (\Exception $e) {
            error_log('Subscription extend end date failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign port to subscription
     * 
     * @param string $id Subscription ID
     * @param string $portId Port ID
     * @return bool Returns true on success, false on failure
     */
    public function assignPort(string $id, string $portId): bool
    {
        return $this->update($id, ['assigned_port_id' => $portId]);
    }

    /**
     * Find expired subscriptions
     * 
     * @return array Returns array of expired subscriptions
     */
    public function findExpired(): array
    {
        try {
            $sql = "SELECT * FROM subscriptions WHERE status = 'ACTIVE' AND end_date < CURDATE()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Find expired subscriptions failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate UUID v4
     * 
     * @return string UUID
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
