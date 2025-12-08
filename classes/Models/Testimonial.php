<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

class Testimonial
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

            $sql = "INSERT INTO testimonials (
                id, customer_name, customer_title, customer_company, customer_image, 
                testimonial_text, rating, display_order, is_featured, status
            ) VALUES (
                :id, :customer_name, :customer_title, :customer_company, :customer_image,
                :testimonial_text, :rating, :display_order, :is_featured, :status
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':customer_name' => $data['customer_name'],
                ':customer_title' => !empty($data['customer_title']) ? $data['customer_title'] : null,
                ':customer_company' => !empty($data['customer_company']) ? $data['customer_company'] : null,
                ':customer_image' => !empty($data['customer_image']) ? $data['customer_image'] : null,
                ':testimonial_text' => $data['testimonial_text'],
                ':rating' => $data['rating'] ?? 5,
                ':display_order' => $data['display_order'] ?? 0,
                ':is_featured' => $data['is_featured'] ?? false,
                ':status' => $data['status'] ?? 'DRAFT'
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Testimonial creation failed: ' . $e->getMessage());
            return false;
        }
    }

    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM testimonials WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log('Testimonial find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['customer_name', 'customer_title', 'customer_company', 'customer_image', 
                            'testimonial_text', 'rating', 'display_order', 'is_featured', 'status'];
            $updateFields = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $updateFields[] = "$key = :$key";
                    if (in_array($key, ['customer_title', 'customer_company', 'customer_image']) && $value === '') {
                        $params[":$key"] = null;
                    } else {
                        $params[":$key"] = $value;
                    }
                }
            }

            if (empty($updateFields)) {
                return false;
            }

            $sql = "UPDATE testimonials SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Testimonial update failed: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM testimonials WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Testimonial deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM testimonials WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['is_featured'])) {
                $sql .= " AND is_featured = :is_featured";
                $params[':is_featured'] = $filters['is_featured'];
            }

            $sql .= " ORDER BY display_order ASC, created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Testimonials getAll failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getPublished(int $limit = 10): array
    {
        return $this->getAll(['status' => 'PUBLISHED'], $limit);
    }

    public function getFeatured(int $limit = 6): array
    {
        return $this->getAll(['status' => 'PUBLISHED', 'is_featured' => true], $limit);
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
