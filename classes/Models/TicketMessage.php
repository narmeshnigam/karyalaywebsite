<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * TicketMessage Model Class
 * 
 * Handles CRUD operations for ticket_messages table
 */
class TicketMessage
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new ticket message
     * 
     * @param array $data Message data (ticket_id, author_id, author_type, content, is_internal, attachments)
     * @return array|false Returns message data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            // Encode attachments as JSON if provided as array
            $attachments = null;
            if (isset($data['attachments'])) {
                $attachments = is_array($data['attachments']) 
                    ? json_encode($data['attachments']) 
                    : $data['attachments'];
            }

            $sql = "INSERT INTO ticket_messages (
                id, ticket_id, author_id, author_type, content, is_internal, attachments
            ) VALUES (
                :id, :ticket_id, :author_id, :author_type, :content, :is_internal, :attachments
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':ticket_id' => $data['ticket_id'],
                ':author_id' => $data['author_id'],
                ':author_type' => $data['author_type'],
                ':content' => $data['content'],
                ':is_internal' => isset($data['is_internal']) ? (int)$data['is_internal'] : 0,
                ':attachments' => $attachments
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Ticket message creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find message by ID
     * 
     * @param string $id Message ID
     * @return array|false Returns message data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM ticket_messages WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['attachments']) {
                $result['attachments'] = json_decode($result['attachments'], true);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Ticket message find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find messages by ticket ID
     * 
     * @param string $ticketId Ticket ID
     * @param bool $includeInternal Whether to include internal notes (default: true)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of messages in chronological order
     */
    public function findByTicketId(string $ticketId, bool $includeInternal = true, int $limit = 1000, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM ticket_messages WHERE ticket_id = :ticket_id";
            
            if (!$includeInternal) {
                $sql .= " AND is_internal = FALSE";
            }
            
            $sql .= " ORDER BY created_at ASC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':ticket_id', $ticketId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON attachments
            foreach ($messages as &$message) {
                if ($message['attachments']) {
                    $message['attachments'] = json_decode($message['attachments'], true);
                }
            }
            
            return $messages;
        } catch (PDOException $e) {
            error_log('Ticket message find by ticket ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find messages by author ID
     * 
     * @param string $authorId Author ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of messages
     */
    public function findByAuthorId(string $authorId, int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM ticket_messages WHERE author_id = :author_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':author_id', $authorId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON attachments
            foreach ($messages as &$message) {
                if ($message['attachments']) {
                    $message['attachments'] = json_decode($message['attachments'], true);
                }
            }
            
            return $messages;
        } catch (PDOException $e) {
            error_log('Ticket message find by author ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update message data
     * 
     * @param string $id Message ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['content', 'is_internal', 'attachments'];
            $updateFields = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    if ($key === 'attachments' && is_array($value)) {
                        $updateFields[] = "$key = :$key";
                        $params[":$key"] = json_encode($value);
                    } else {
                        $updateFields[] = "$key = :$key";
                        $params[":$key"] = $value;
                    }
                }
            }

            if (empty($updateFields)) {
                return false;
            }

            $sql = "UPDATE ticket_messages SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Ticket message update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete message
     * 
     * @param string $id Message ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM ticket_messages WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Ticket message deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all messages with optional filters
     * 
     * @param array $filters Optional filters (ticket_id, author_id, author_type, is_internal)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of messages
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM ticket_messages WHERE 1=1";
            $params = [];

            if (isset($filters['ticket_id'])) {
                $sql .= " AND ticket_id = :ticket_id";
                $params[':ticket_id'] = $filters['ticket_id'];
            }

            if (isset($filters['author_id'])) {
                $sql .= " AND author_id = :author_id";
                $params[':author_id'] = $filters['author_id'];
            }

            if (isset($filters['author_type'])) {
                $sql .= " AND author_type = :author_type";
                $params[':author_type'] = $filters['author_type'];
            }

            if (isset($filters['is_internal'])) {
                $sql .= " AND is_internal = :is_internal";
                $params[':is_internal'] = $filters['is_internal'];
            }

            $sql .= " ORDER BY created_at ASC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON attachments
            foreach ($messages as &$message) {
                if ($message['attachments']) {
                    $message['attachments'] = json_decode($message['attachments'], true);
                }
            }
            
            return $messages;
        } catch (PDOException $e) {
            error_log('Ticket message findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count messages for a ticket
     * 
     * @param string $ticketId Ticket ID
     * @param bool $includeInternal Whether to include internal notes
     * @return int Returns count of messages
     */
    public function countByTicketId(string $ticketId, bool $includeInternal = true): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = :ticket_id";
            
            if (!$includeInternal) {
                $sql .= " AND is_internal = FALSE";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':ticket_id' => $ticketId]);
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Ticket message count failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get customer-visible messages for a ticket
     * 
     * @param string $ticketId Ticket ID
     * @return array Returns array of customer-visible messages
     */
    public function findCustomerVisibleByTicketId(string $ticketId): array
    {
        return $this->findByTicketId($ticketId, false);
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
