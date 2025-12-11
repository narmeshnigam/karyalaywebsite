<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * Port Model Class
 * 
 * Handles CRUD operations for ports table
 */
class Port
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new port
     * 
     * @param array $data Port data (instance_url, db_host, db_name, db_username, db_password, status, assigned_subscription_id, assigned_at, server_region, notes, setup_instructions)
     * @return array|false Returns port data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();
            error_log('Port::create - Starting with ID: ' . $id);
            error_log('Port::create - Data received: ' . json_encode($data));

            $sql = "INSERT INTO ports (
                id, instance_url, db_host, db_name, db_username, db_password, 
                status, assigned_subscription_id, assigned_at, server_region, notes, setup_instructions
            ) VALUES (
                :id, :instance_url, :db_host, :db_name, :db_username, :db_password, 
                :status, :assigned_subscription_id, :assigned_at, :server_region, :notes, :setup_instructions
            )";

            error_log('Port::create - Preparing SQL statement');
            $stmt = $this->db->prepare($sql);
            
            $params = [
                ':id' => $id,
                ':instance_url' => $data['instance_url'],
                ':db_host' => $data['db_host'] ?? null,
                ':db_name' => $data['db_name'] ?? null,
                ':db_username' => $data['db_username'] ?? null,
                ':db_password' => $data['db_password'] ?? null,
                ':status' => $data['status'] ?? 'AVAILABLE',
                ':assigned_subscription_id' => $data['assigned_subscription_id'] ?? null,
                ':assigned_at' => $data['assigned_at'] ?? null,
                ':server_region' => $data['server_region'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':setup_instructions' => $data['setup_instructions'] ?? null
            ];
            
            error_log('Port::create - Executing with params (password hidden): ' . json_encode(array_merge($params, [':db_password' => '***HIDDEN***'])));
            $stmt->execute($params);
            error_log('Port::create - Execute successful, fetching created port');

            $result = $this->findById($id);
            error_log('Port::create - Returning: ' . ($result ? 'port data' : 'false'));
            return $result;
        } catch (PDOException $e) {
            error_log('Port::create FAILED - PDOException: ' . $e->getMessage());
            error_log('Port::create FAILED - SQL State: ' . $e->getCode());
            error_log('Port::create FAILED - Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Find port by ID
     * 
     * @param string $id Port ID
     * @return array|false Returns port data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM ports WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Port find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find port by subscription ID
     * 
     * @param string $subscriptionId Subscription ID
     * @return array|false Returns port data or false if not found
     */
    public function findBySubscriptionId(string $subscriptionId)
    {
        try {
            $sql = "SELECT * FROM ports WHERE assigned_subscription_id = :subscription_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':subscription_id' => $subscriptionId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Port find by subscription ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find available ports (plan-agnostic)
     * 
     * @param int $limit Optional limit
     * @return array Returns array of available ports
     */
    public function findAvailable(int $limit = 1): array
    {
        try {
            $sql = "SELECT * FROM ports WHERE status = 'AVAILABLE' ORDER BY created_at ASC LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Port find available failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count all available ports (plan-agnostic)
     * 
     * @return int Returns count of available ports
     */
    public function countAvailable(): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM ports WHERE status = 'AVAILABLE'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Port count available failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update port data
     * 
     * @param string $id Port ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = [
                'instance_url', 'db_host', 'db_name', 'db_username', 'db_password',
                'status', 'assigned_subscription_id', 'assigned_at', 'server_region', 'notes', 'setup_instructions'
            ];
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

            $sql = "UPDATE ports SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Port update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete port
     * 
     * @param string $id Port ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM ports WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Port deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all ports with optional filters
     * 
     * @param array $filters Optional filters (status, assigned_subscription_id)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of ports
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM ports WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['assigned_subscription_id'])) {
                $sql .= " AND assigned_subscription_id = :assigned_subscription_id";
                $params[':assigned_subscription_id'] = $filters['assigned_subscription_id'];
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
            error_log('Port findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update port status
     * 
     * @param string $id Port ID
     * @param string $status New status (AVAILABLE, RESERVED, ASSIGNED, DISABLED)
     * @return bool Returns true on success, false on failure
     */
    public function updateStatus(string $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Assign port to subscription
     * 
     * @param string $id Port ID
     * @param string $subscriptionId Subscription ID
     * @param string $assignedAt Assignment timestamp
     * @return bool Returns true on success, false on failure
     */
    public function assignToSubscription(string $id, string $subscriptionId, string $assignedAt): bool
    {
        return $this->update($id, [
            'status' => 'ASSIGNED',
            'assigned_subscription_id' => $subscriptionId,
            'assigned_at' => $assignedAt
        ]);
    }

    /**
     * Release port from subscription
     * 
     * @param string $id Port ID
     * @return bool Returns true on success, false on failure
     */
    public function release(string $id): bool
    {
        return $this->update($id, [
            'status' => 'AVAILABLE',
            'assigned_subscription_id' => null,
            'assigned_at' => null
        ]);
    }

    /**
     * Check if port exists by instance URL
     * 
     * @param string $instanceUrl Instance URL
     * @param string|null $excludeId Optional port ID to exclude from check
     * @return bool Returns true if port exists, false otherwise
     */
    public function portExists(string $instanceUrl, ?string $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM ports WHERE instance_url = :instance_url";
            $params = [':instance_url' => $instanceUrl];

            if ($excludeId !== null) {
                $sql .= " AND id != :id";
                $params[':id'] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Port exists check failed: ' . $e->getMessage());
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
