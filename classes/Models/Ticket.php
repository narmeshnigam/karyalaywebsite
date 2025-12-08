<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * Ticket Model Class
 * 
 * Handles CRUD operations for tickets table
 */
class Ticket
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new ticket
     * 
     * @param array $data Ticket data (customer_id, subscription_id, subject, category, priority, status, assigned_to)
     * @return array|false Returns ticket data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO tickets (
                id, customer_id, subscription_id, subject, category, priority, status, assigned_to
            ) VALUES (
                :id, :customer_id, :subscription_id, :subject, :category, :priority, :status, :assigned_to
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':customer_id' => $data['customer_id'],
                ':subscription_id' => $data['subscription_id'] ?? null,
                ':subject' => $data['subject'],
                ':category' => $data['category'] ?? null,
                ':priority' => $data['priority'] ?? 'MEDIUM',
                ':status' => $data['status'] ?? 'OPEN',
                ':assigned_to' => $data['assigned_to'] ?? null
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Ticket creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find ticket by ID
     * 
     * @param string $id Ticket ID
     * @return array|false Returns ticket data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM tickets WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Ticket find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find tickets by customer ID
     * 
     * @param string $customerId Customer ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of tickets
     */
    public function findByCustomerId(string $customerId, int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM tickets WHERE customer_id = :customer_id ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Ticket find by customer ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find tickets by subscription ID
     * 
     * @param string $subscriptionId Subscription ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of tickets
     */
    public function findBySubscriptionId(string $subscriptionId, int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM tickets WHERE subscription_id = :subscription_id ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':subscription_id', $subscriptionId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Ticket find by subscription ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find tickets assigned to a user
     * 
     * @param string $userId User ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of tickets
     */
    public function findByAssignedTo(string $userId, int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM tickets WHERE assigned_to = :assigned_to ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':assigned_to', $userId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Ticket find by assigned to failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update ticket data
     * 
     * @param string $id Ticket ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['subject', 'category', 'priority', 'status', 'assigned_to', 'subscription_id'];
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

            $sql = "UPDATE tickets SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Ticket update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete ticket
     * 
     * @param string $id Ticket ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM tickets WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Ticket deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all tickets with optional filters
     * 
     * @param array $filters Optional filters (customer_id, status, priority, assigned_to, category)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of tickets
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM tickets WHERE 1=1";
            $params = [];

            if (isset($filters['customer_id'])) {
                $sql .= " AND customer_id = :customer_id";
                $params[':customer_id'] = $filters['customer_id'];
            }

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['priority'])) {
                $sql .= " AND priority = :priority";
                $params[':priority'] = $filters['priority'];
            }

            if (isset($filters['assigned_to'])) {
                $sql .= " AND assigned_to = :assigned_to";
                $params[':assigned_to'] = $filters['assigned_to'];
            }

            if (isset($filters['category'])) {
                $sql .= " AND category = :category";
                $params[':category'] = $filters['category'];
            }

            $sql .= " ORDER BY updated_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Ticket findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update ticket status
     * 
     * @param string $id Ticket ID
     * @param string $status New status (OPEN, IN_PROGRESS, WAITING_ON_CUSTOMER, RESOLVED, CLOSED)
     * @return bool Returns true on success, false on failure
     */
    public function updateStatus(string $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Assign ticket to user
     * 
     * @param string $id Ticket ID
     * @param string $userId User ID
     * @return bool Returns true on success, false on failure
     */
    public function assignTo(string $id, string $userId): bool
    {
        return $this->update($id, ['assigned_to' => $userId]);
    }

    /**
     * Check if ticket is closed
     * 
     * @param string $id Ticket ID
     * @return bool Returns true if ticket is closed, false otherwise
     */
    public function isClosed(string $id): bool
    {
        $ticket = $this->findById($id);
        return $ticket && $ticket['status'] === 'CLOSED';
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
