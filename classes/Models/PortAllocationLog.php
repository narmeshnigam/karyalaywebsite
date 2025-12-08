<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * PortAllocationLog Model Class
 * 
 * Handles CRUD operations for port_allocation_logs table
 */
class PortAllocationLog
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new port allocation log entry
     * 
     * @param array $data Log data (port_id, subscription_id, customer_id, action, performed_by, timestamp)
     * @return array|false Returns log data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO port_allocation_logs (
                id, port_id, subscription_id, customer_id, action, performed_by, timestamp
            ) VALUES (
                :id, :port_id, :subscription_id, :customer_id, :action, :performed_by, :timestamp
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':port_id' => $data['port_id'],
                ':subscription_id' => $data['subscription_id'],
                ':customer_id' => $data['customer_id'],
                ':action' => $data['action'],
                ':performed_by' => $data['performed_by'] ?? null,
                ':timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s')
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('PortAllocationLog creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find log entry by ID
     * 
     * @param string $id Log ID
     * @return array|false Returns log data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM port_allocation_logs WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('PortAllocationLog find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find logs by port ID
     * 
     * @param string $portId Port ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of log entries
     */
    public function findByPortId(string $portId, int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM port_allocation_logs WHERE port_id = :port_id ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':port_id', $portId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PortAllocationLog find by port ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find logs by subscription ID
     * 
     * @param string $subscriptionId Subscription ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of log entries
     */
    public function findBySubscriptionId(string $subscriptionId, int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM port_allocation_logs WHERE subscription_id = :subscription_id ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':subscription_id', $subscriptionId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PortAllocationLog find by subscription ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find logs by customer ID
     * 
     * @param string $customerId Customer ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of log entries
     */
    public function findByCustomerId(string $customerId, int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM port_allocation_logs WHERE customer_id = :customer_id ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PortAllocationLog find by customer ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all logs with optional filters
     * 
     * @param array $filters Optional filters (port_id, subscription_id, customer_id, action, performed_by)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of log entries
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM port_allocation_logs WHERE 1=1";
            $params = [];

            if (isset($filters['port_id'])) {
                $sql .= " AND port_id = :port_id";
                $params[':port_id'] = $filters['port_id'];
            }

            if (isset($filters['subscription_id'])) {
                $sql .= " AND subscription_id = :subscription_id";
                $params[':subscription_id'] = $filters['subscription_id'];
            }

            if (isset($filters['customer_id'])) {
                $sql .= " AND customer_id = :customer_id";
                $params[':customer_id'] = $filters['customer_id'];
            }

            if (isset($filters['action'])) {
                $sql .= " AND action = :action";
                $params[':action'] = $filters['action'];
            }

            if (isset($filters['performed_by'])) {
                $sql .= " AND performed_by = :performed_by";
                $params[':performed_by'] = $filters['performed_by'];
            }

            $sql .= " ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PortAllocationLog findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Log port assignment
     * 
     * @param string $portId Port ID
     * @param string $subscriptionId Subscription ID
     * @param string $customerId Customer ID
     * @param string|null $performedBy Admin user ID (null for automatic assignment)
     * @return array|false Returns log entry or false on failure
     */
    public function logAssignment(string $portId, string $subscriptionId, string $customerId, ?string $performedBy = null)
    {
        return $this->create([
            'port_id' => $portId,
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
            'action' => 'ASSIGNED',
            'performed_by' => $performedBy
        ]);
    }

    /**
     * Log port reassignment
     * 
     * @param string $portId Port ID
     * @param string $subscriptionId Subscription ID
     * @param string $customerId Customer ID
     * @param string $performedBy Admin user ID
     * @return array|false Returns log entry or false on failure
     */
    public function logReassignment(string $portId, string $subscriptionId, string $customerId, string $performedBy)
    {
        return $this->create([
            'port_id' => $portId,
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
            'action' => 'REASSIGNED',
            'performed_by' => $performedBy
        ]);
    }

    /**
     * Log port release
     * 
     * @param string $portId Port ID
     * @param string $subscriptionId Subscription ID
     * @param string $customerId Customer ID
     * @param string|null $performedBy Admin user ID (null for automatic release)
     * @return array|false Returns log entry or false on failure
     */
    public function logRelease(string $portId, string $subscriptionId, string $customerId, ?string $performedBy = null)
    {
        return $this->create([
            'port_id' => $portId,
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
            'action' => 'RELEASED',
            'performed_by' => $performedBy
        ]);
    }

    /**
     * Delete log entry
     * 
     * @param string $id Log ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM port_allocation_logs WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('PortAllocationLog deletion failed: ' . $e->getMessage());
            return false;
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
