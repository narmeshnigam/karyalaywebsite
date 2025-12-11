<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * FAQ Model Class
 * 
 * Handles CRUD operations for FAQs table
 */
class Faq
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new FAQ
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO faqs (id, question, answer, category, display_order, status)
                    VALUES (:id, :question, :answer, :category, :display_order, :status)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':question' => $data['question'],
                ':answer' => $data['answer'],
                ':category' => $data['category'] ?? 'General',
                ':display_order' => $data['display_order'] ?? 0,
                ':status' => $data['status'] ?? 'DRAFT'
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('FAQ creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find FAQ by ID
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM faqs WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log('FAQ find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update FAQ
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['question', 'answer', 'category', 'display_order', 'status'];
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

            $sql = "UPDATE faqs SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('FAQ update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete FAQ
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM faqs WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('FAQ deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all FAQs with optional filters
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM faqs WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['category'])) {
                $sql .= " AND category = :category";
                $params[':category'] = $filters['category'];
            }

            $sql .= " ORDER BY category ASC, display_order ASC, created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('FAQ findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get FAQs grouped by category
     */
    public function findAllGroupedByCategory(array $filters = []): array
    {
        try {
            $sql = "SELECT * FROM faqs WHERE status = 'PUBLISHED' ORDER BY category ASC, display_order ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $grouped = [];
            foreach ($faqs as $faq) {
                $category = $faq['category'];
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][] = $faq;
            }

            return $grouped;
        } catch (PDOException $e) {
            error_log('FAQ findAllGroupedByCategory failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all unique categories
     */
    public function getCategories(): array
    {
        try {
            $sql = "SELECT DISTINCT category FROM faqs ORDER BY category ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('FAQ getCategories failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count FAQs
     */
    public function count(array $filters = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM faqs WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['category'])) {
                $sql .= " AND category = :category";
                $params[':category'] = $filters['category'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('FAQ count failed: ' . $e->getMessage());
            return 0;
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
