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
     * @param array $data Order data (customer_id, plan_id, amount, currency, status, pg_order_id, pg_payment_id, payment_method, billing_*)
     * @return array|false Returns order data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO orders (
                id, customer_id, plan_id, amount, currency, status, pg_order_id, pg_payment_id, payment_method,
                billing_full_name, billing_business_name, billing_business_tax_id,
                billing_address_line1, billing_address_line2, billing_city, billing_state,
                billing_postal_code, billing_country, billing_phone
            ) VALUES (
                :id, :customer_id, :plan_id, :amount, :currency, :status, :pg_order_id, :pg_payment_id, :payment_method,
                :billing_full_name, :billing_business_name, :billing_business_tax_id,
                :billing_address_line1, :billing_address_line2, :billing_city, :billing_state,
                :billing_postal_code, :billing_country, :billing_phone
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':customer_id' => $data['customer_id'],
                ':plan_id' => $data['plan_id'],
                ':amount' => $data['amount'],
                ':currency' => $data['currency'] ?? 'USD',
                ':status' => $data['status'] ?? 'PENDING',
                ':pg_order_id' => $data['pg_order_id'] ?? null,
                ':pg_payment_id' => $data['pg_payment_id'] ?? null,
                ':payment_method' => $data['payment_method'] ?? null,
                ':billing_full_name' => $data['billing_full_name'] ?? null,
                ':billing_business_name' => $data['billing_business_name'] ?? null,
                ':billing_business_tax_id' => $data['billing_business_tax_id'] ?? null,
                ':billing_address_line1' => $data['billing_address_line1'] ?? null,
                ':billing_address_line2' => $data['billing_address_line2'] ?? null,
                ':billing_city' => $data['billing_city'] ?? null,
                ':billing_state' => $data['billing_state'] ?? null,
                ':billing_postal_code' => $data['billing_postal_code'] ?? null,
                ':billing_country' => $data['billing_country'] ?? null,
                ':billing_phone' => $data['billing_phone'] ?? null
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
     * Find order by payment gateway order ID
     * 
     * @param string $pgOrderId Payment gateway order ID
     * @return array|false Returns order data or false if not found
     */
    public function findByPgOrderId(string $pgOrderId)
    {
        try {
            $sql = "SELECT * FROM orders WHERE pg_order_id = :pg_order_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':pg_order_id' => $pgOrderId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Order find by PG order ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find order by payment gateway payment ID
     * 
     * @param string $pgPaymentId Payment gateway payment ID
     * @return array|false Returns order data or false if not found
     */
    public function findByPgPaymentId(string $pgPaymentId)
    {
        try {
            $sql = "SELECT * FROM orders WHERE pg_payment_id = :pg_payment_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':pg_payment_id' => $pgPaymentId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Order find by PG payment ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Legacy method for backward compatibility
     * @deprecated Use findByPgOrderId() instead
     */
    public function findByPaymentGatewayId(string $paymentGatewayId)
    {
        return $this->findByPgOrderId($paymentGatewayId);
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
            $allowedFields = ['status', 'pg_order_id', 'pg_payment_id', 'payment_method', 'amount', 'currency', 'invoice_id'];
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
     * Update invoice ID for an order
     * 
     * @param string $id Order ID
     * @param string $invoiceId Invoice ID
     * @return bool Returns true on success, false on failure
     */
    public function updateInvoiceId(string $id, string $invoiceId): bool
    {
        return $this->update($id, ['invoice_id' => $invoiceId]);
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
