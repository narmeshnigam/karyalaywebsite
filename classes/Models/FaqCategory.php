<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * FAQ Category Model Class
 */
class FaqCategory
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
            $sql = "INSERT INTO faq_categories (id, name, slug, description, display_order, status)
                    VALUES (:id, :name, :slug, :description, :display_order, :status)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':display_order' => $data['display_order'] ?? 0,
                ':status' => $data['status'] ?? 'ACTIVE'
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('FAQ Category creation failed: ' . $e->getMessage());
            return false;
        }
    }

    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM faq_categories WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log('FAQ Category find failed: ' . $e->getMessage());
            return false;
        }
    }

    public function findBySlug(string $slug)
    {
        try {
            $sql = "SELECT * FROM faq_categories WHERE slug = :slug";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log('FAQ Category find by slug failed: ' . $e->getMessage());
            return false;
        }
    }

    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['name', 'slug', 'description', 'display_order', 'status'];
            $updateFields = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $updateFields[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }

            if (empty($updateFields)) return false;

            $sql = "UPDATE faq_categories SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('FAQ Category update failed: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM faq_categories WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('FAQ Category deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    public function findAll(array $filters = []): array
    {
        try {
            $sql = "SELECT * FROM faq_categories WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            $sql .= " ORDER BY display_order ASC, name ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('FAQ Category findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM faq_categories WHERE slug = :slug";
            $params = [':slug' => $slug];

            if ($excludeId) {
                $sql .= " AND id != :id";
                $params[':id'] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
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
