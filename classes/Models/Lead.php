<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

class Lead
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            // Check if column is 'company' or 'company_name'
            $columns = $this->db->query("SHOW COLUMNS FROM leads LIKE 'company%'")->fetchAll(PDO::FETCH_COLUMN);
            $companyColumn = in_array('company_name', $columns) ? 'company_name' : 'company';
            
            $sql = "INSERT INTO leads (
                id, name, email, phone, {$companyColumn}, message, source, status
            ) VALUES (
                :id, :name, :email, :phone, :company, :message, :source, :status
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':phone' => !empty($data['phone']) ? $data['phone'] : null,
                ':company' => !empty($data['company']) ? $data['company'] : null,
                ':message' => !empty($data['message']) ? $data['message'] : null,
                ':source' => $data['source'] ?? 'CONTACT_FORM',
                ':status' => 'NEW'
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Lead creation failed: ' . $e->getMessage());
            error_log('SQL Error Code: ' . $e->getCode());
            return false;
        }
    }

    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM leads WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log('Lead find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM leads WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $sql .= " AND (name LIKE :search OR email LIKE :search OR company LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
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
            error_log('Leads getAll failed: ' . $e->getMessage());
            return [];
        }
    }

    public function countAll(array $filters = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM leads WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Leads countAll failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function updateStatus(string $id, string $status): bool
    {
        try {
            $validStatuses = ['NEW', 'CONTACTED', 'QUALIFIED', 'CONVERTED', 'LOST'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }

            $sql = "UPDATE leads SET status = :status, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':status' => $status, ':id' => $id]);
        } catch (PDOException $e) {
            error_log('Lead status update failed: ' . $e->getMessage());
            return false;
        }
    }

    public function markAsContacted(string $id, ?string $notes = null): bool
    {
        return $this->updateStatus($id, 'CONTACTED');
    }

    public function addNote(string $leadId, string $userId, string $note): array|false
    {
        try {
            $noteId = $this->generateUuid();
            
            $sql = "INSERT INTO lead_notes (id, lead_id, user_id, note) VALUES (:id, :lead_id, :user_id, :note)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $noteId,
                ':lead_id' => $leadId,
                ':user_id' => $userId,
                ':note' => $note
            ]);

            // Update notes count on lead
            $this->updateNotesCount($leadId);

            return $this->getNoteById($noteId);
        } catch (PDOException $e) {
            error_log('Lead note creation failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getNoteById(string $id): array|false
    {
        try {
            $sql = "SELECT ln.*, u.name as user_name 
                    FROM lead_notes ln 
                    LEFT JOIN users u ON ln.user_id = u.id 
                    WHERE ln.id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log('Get note by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getNotes(string $leadId): array
    {
        try {
            $sql = "SELECT ln.*, u.name as user_name 
                    FROM lead_notes ln 
                    LEFT JOIN users u ON ln.user_id = u.id 
                    WHERE ln.lead_id = :lead_id 
                    ORDER BY ln.created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':lead_id' => $leadId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Get lead notes failed: ' . $e->getMessage());
            return [];
        }
    }

    public function deleteNote(string $noteId, string $leadId): bool
    {
        try {
            $sql = "DELETE FROM lead_notes WHERE id = :id AND lead_id = :lead_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([':id' => $noteId, ':lead_id' => $leadId]);
            
            if ($result) {
                $this->updateNotesCount($leadId);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log('Delete lead note failed: ' . $e->getMessage());
            return false;
        }
    }

    private function updateNotesCount(string $leadId): void
    {
        try {
            $sql = "UPDATE leads SET notes_count = (SELECT COUNT(*) FROM lead_notes WHERE lead_id = :lead_id) WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':lead_id' => $leadId, ':id' => $leadId]);
        } catch (PDOException $e) {
            // Silently fail - notes_count column might not exist yet
            error_log('Update notes count failed: ' . $e->getMessage());
        }
    }

    public function getStatusCounts(): array
    {
        try {
            $sql = "SELECT status, COUNT(*) as count FROM leads GROUP BY status";
            $stmt = $this->db->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $counts = [
                'NEW' => 0,
                'CONTACTED' => 0,
                'QUALIFIED' => 0,
                'CONVERTED' => 0,
                'LOST' => 0,
                'total' => 0
            ];
            
            foreach ($results as $row) {
                $counts[$row['status']] = (int) $row['count'];
                $counts['total'] += (int) $row['count'];
            }
            
            return $counts;
        } catch (PDOException $e) {
            error_log('Get status counts failed: ' . $e->getMessage());
            return ['NEW' => 0, 'CONTACTED' => 0, 'QUALIFIED' => 0, 'CONVERTED' => 0, 'LOST' => 0, 'total' => 0];
        }
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
