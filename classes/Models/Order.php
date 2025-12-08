<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * Order Model Class
 * 
 * Handles CRUD operations for orders table
 */
class Order
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new order
     * 
     * @param array $data Order data (customer_id, plan_id, amount, currency, status, payment_gateway_id, payment_method)
     * @return array|false Returns order data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO orders (
                id, customer_id, plan_id, amount, currency, status, payment_gateway_id, payment_method
            ) VALUES (
                :id, :customer_id, :plan_id, :amount, :currency, :status, :payment_gateway_id, :payment_method
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':customer_id' => $data['customer_id'],
                ':plan_id' => $data['plan_id'],
                ':amount' => $data['amount'],
                ':currency' => $data['currency'] ?? 'USD',
                ':status' => $data['status'] ?? 'PENDING',
                ':payment_gateway_id' => $data['payment_gateway_id'] ?? null,
                ':payment_method' => $data['payment_method'] ?? null
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Order creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find order by ID
     * 
     * @param string $id Order ID
     * @return array|false Returns order data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM orders WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Order find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find orders by customer ID
     * 
     * @param string $customerId Customer ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of orders
     */
    public function findByCustomerId(string $customerId, int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM orders WHERE customer_id = :customer_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Order find by customer ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find order by payment gateway ID
     * 
     * @param string $paymentGatewayId Payment gateway ID
     * @return array|false Returns order data or false if not found
     */
    public function findByPaymentGatewayId(string $paymentGatewayId)
    {
        try {
            $sql = "SELECT * FROM orders WHERE payment_gateway_id = :payment_gateway_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':payment_gateway_id' => $paymentGatewayId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Order find by payment gateway ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update order data
     * 
     * @param string $id Order ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['status', 'payment_gateway_id', 'payment_method', 'amount', 'currency'];
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

            $sql = "UPDATE orders SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Order update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete order
     * 
     * @param string $id Order ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM orders WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Order deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all orders with optional filters
     * 
     * @param array $filters Optional filters (customer_id, status, plan_id)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of orders
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM orders WHERE 1=1";
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
            error_log('Order findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update order status
     * 
     * @param string $id Order ID
     * @param string $status New status (PENDING, SUCCESS, FAILED, CANCELLED)
     * @return bool Returns true on success, false on failure
     */
    public function updateStatus(string $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
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
