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
     * @param array $data Log data (port_id, subscription_id, customer_id, action, performed_by, timestamp, notes)
     * @return array|false Returns log data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            // Log the attempt for debugging
            error_log('PortAllocationLog::create - Attempting to create log entry: ' . json_encode([
                'port_id' => $data['port_id'] ?? 'NULL',
                'subscription_id' => $data['subscription_id'] ?? 'NULL',
                'customer_id' => $data['customer_id'] ?? 'NULL',
                'action' => $data['action'] ?? 'NULL',
                'performed_by' => $data['performed_by'] ?? 'NULL'
            ]));

            $sql = "INSERT INTO port_allocation_logs (
                id, port_id, subscription_id, customer_id, action, performed_by, timestamp, notes
            ) VALUES (
                :id, :port_id, :subscription_id, :customer_id, :action, :performed_by, :timestamp, :notes
            )";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':id' => $id,
                ':port_id' => $data['port_id'],
                ':subscription_id' => $data['subscription_id'] ?? null,
                ':customer_id' => $data['customer_id'] ?? null,
                ':action' => $data['action'],
                ':performed_by' => $data['performed_by'] ?? null,
                ':timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
                ':notes' => $data['notes'] ?? null
            ]);

            if ($result) {
                error_log('PortAllocationLog::create - Successfully created log entry with ID: ' . $id);
            }

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('PortAllocationLog creation failed: ' . $e->getMessage());
            error_log('PortAllocationLog creation failed - SQL State: ' . $e->getCode());
            error_log('PortAllocationLog creation failed - Data: ' . json_encode($data));
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
     * @param array $filters Optional filters (port_id, subscription_id, customer_id, action, performed_by, date_from, date_to, search)
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

            if (isset($filters['date_from'])) {
                $sql .= " AND DATE(timestamp) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (isset($filters['date_to'])) {
                $sql .= " AND DATE(timestamp) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
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
     * Count logs with optional filters
     * 
     * @param array $filters Optional filters (port_id, subscription_id, customer_id, action, performed_by, date_from, date_to)
     * @return int Returns count of log entries
     */
    public function countAll(array $filters = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM port_allocation_logs WHERE 1=1";
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

            if (isset($filters['date_from'])) {
                $sql .= " AND DATE(timestamp) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (isset($filters['date_to'])) {
                $sql .= " AND DATE(timestamp) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('PortAllocationLog countAll failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all logs with related data (port, customer, subscription, performer)
     * 
     * @param array $filters Optional filters
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of log entries with related data
     */
    public function findAllWithRelations(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT 
                        pal.*,
                        p.instance_url as port_instance_url,
                        p.status as port_status,
                        c.name as customer_name,
                        c.email as customer_email,
                        s.id as subscription_id,
                        pl.name as plan_name,
                        performer.name as performed_by_name,
                        performer.email as performed_by_email
                    FROM port_allocation_logs pal
                    LEFT JOIN ports p ON pal.port_id = p.id
                    LEFT JOIN users c ON pal.customer_id = c.id
                    LEFT JOIN subscriptions s ON pal.subscription_id = s.id
                    LEFT JOIN plans pl ON s.plan_id = pl.id
                    LEFT JOIN users performer ON pal.performed_by = performer.id
                    WHERE 1=1";
            $params = [];

            if (isset($filters['port_id'])) {
                $sql .= " AND pal.port_id = :port_id";
                $params[':port_id'] = $filters['port_id'];
            }

            if (isset($filters['subscription_id'])) {
                $sql .= " AND pal.subscription_id = :subscription_id";
                $params[':subscription_id'] = $filters['subscription_id'];
            }

            if (isset($filters['customer_id'])) {
                $sql .= " AND pal.customer_id = :customer_id";
                $params[':customer_id'] = $filters['customer_id'];
            }

            if (isset($filters['action'])) {
                $sql .= " AND pal.action = :action";
                $params[':action'] = $filters['action'];
            }

            if (isset($filters['performed_by'])) {
                $sql .= " AND pal.performed_by = :performed_by";
                $params[':performed_by'] = $filters['performed_by'];
            }

            if (isset($filters['date_from'])) {
                $sql .= " AND DATE(pal.timestamp) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (isset($filters['date_to'])) {
                $sql .= " AND DATE(pal.timestamp) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            if (isset($filters['plan_id'])) {
                $sql .= " AND s.plan_id = :plan_id";
                $params[':plan_id'] = $filters['plan_id'];
            }

            if (isset($filters['search'])) {
                $sql .= " AND (p.instance_url LIKE :search OR c.name LIKE :search OR c.email LIKE :search OR performer.name LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            $sql .= " ORDER BY pal.timestamp DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PortAllocationLog findAllWithRelations failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count logs with related data filters
     * 
     * @param array $filters Optional filters
     * @return int Returns count of log entries
     */
    public function countAllWithRelations(array $filters = []): int
    {
        try {
            $sql = "SELECT COUNT(*) 
                    FROM port_allocation_logs pal
                    LEFT JOIN ports p ON pal.port_id = p.id
                    LEFT JOIN users c ON pal.customer_id = c.id
                    LEFT JOIN subscriptions s ON pal.subscription_id = s.id
                    LEFT JOIN plans pl ON s.plan_id = pl.id
                    LEFT JOIN users performer ON pal.performed_by = performer.id
                    WHERE 1=1";
            $params = [];

            if (isset($filters['port_id'])) {
                $sql .= " AND pal.port_id = :port_id";
                $params[':port_id'] = $filters['port_id'];
            }

            if (isset($filters['subscription_id'])) {
                $sql .= " AND pal.subscription_id = :subscription_id";
                $params[':subscription_id'] = $filters['subscription_id'];
            }

            if (isset($filters['customer_id'])) {
                $sql .= " AND pal.customer_id = :customer_id";
                $params[':customer_id'] = $filters['customer_id'];
            }

            if (isset($filters['action'])) {
                $sql .= " AND pal.action = :action";
                $params[':action'] = $filters['action'];
            }

            if (isset($filters['performed_by'])) {
                $sql .= " AND pal.performed_by = :performed_by";
                $params[':performed_by'] = $filters['performed_by'];
            }

            if (isset($filters['date_from'])) {
                $sql .= " AND DATE(pal.timestamp) >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }

            if (isset($filters['date_to'])) {
                $sql .= " AND DATE(pal.timestamp) <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }

            if (isset($filters['plan_id'])) {
                $sql .= " AND s.plan_id = :plan_id";
                $params[':plan_id'] = $filters['plan_id'];
            }

            if (isset($filters['search'])) {
                $sql .= " AND (p.instance_url LIKE :search OR c.name LIKE :search OR c.email LIKE :search OR performer.name LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('PortAllocationLog countAllWithRelations failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get distinct actions from logs
     * 
     * @return array Returns array of distinct action values
     */
    public function getDistinctActions(): array
    {
        try {
            $sql = "SELECT DISTINCT action FROM port_allocation_logs ORDER BY action";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('PortAllocationLog getDistinctActions failed: ' . $e->getMessage());
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
     * Log port status change
     * 
     * @param string $portId Port ID
     * @param string $action Action type (STATUS_CHANGED, DISABLED, ENABLED, RESERVED, MADE_AVAILABLE)
     * @param string|null $subscriptionId Subscription ID (if applicable)
     * @param string|null $customerId Customer ID (if applicable)
     * @param string|null $performedBy Admin user ID
     * @param string|null $notes Additional notes about the change
     * @return array|false Returns log entry or false on failure
     */
    public function logStatusChange(string $portId, string $action, ?string $subscriptionId = null, ?string $customerId = null, ?string $performedBy = null, ?string $notes = null)
    {
        return $this->create([
            'port_id' => $portId,
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
            'action' => $action,
            'performed_by' => $performedBy,
            'notes' => $notes
        ]);
    }

    /**
     * Log port unassignment (different from release - keeps subscription link)
     * 
     * @param string $portId Port ID
     * @param string $subscriptionId Subscription ID
     * @param string $customerId Customer ID
     * @param string|null $performedBy Admin user ID
     * @return array|false Returns log entry or false on failure
     */
    public function logUnassignment(string $portId, string $subscriptionId, string $customerId, ?string $performedBy = null)
    {
        return $this->create([
            'port_id' => $portId,
            'subscription_id' => $subscriptionId,
            'customer_id' => $customerId,
            'action' => 'UNASSIGNED',
            'performed_by' => $performedBy
        ]);
    }

    /**
     * Log port creation
     * 
     * @param string $portId Port ID
     * @param string|null $performedBy Admin user ID
     * @return array|false Returns log entry or false on failure
     */
    public function logCreation(string $portId, ?string $performedBy = null)
    {
        return $this->create([
            'port_id' => $portId,
            'subscription_id' => null,
            'customer_id' => null,
            'action' => 'CREATED',
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
