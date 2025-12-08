<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * Plan Model Class
 * 
 * Handles CRUD operations for plans table
 */
class Plan
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new plan
     * 
     * @param array $data Plan data (name, slug, description, price, currency, billing_period_months, features, status)
     * @return array|false Returns plan data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO plans (
                id, name, slug, description, price, currency, billing_period_months, features, status
            ) VALUES (
                :id, :name, :slug, :description, :price, :currency, :billing_period_months, :features, :status
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':price' => $data['price'],
                ':currency' => $data['currency'] ?? 'USD',
                ':billing_period_months' => $data['billing_period_months'],
                ':features' => isset($data['features']) ? json_encode($data['features']) : null,
                ':status' => $data['status'] ?? 'ACTIVE'
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Plan creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find plan by ID
     * 
     * @param string $id Plan ID
     * @return array|false Returns plan data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM plans WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['features'])) {
                $result['features'] = json_decode($result['features'], true);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Plan find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find plan by slug
     * 
     * @param string $slug Plan slug
     * @return array|false Returns plan data or false if not found
     */
    public function findBySlug(string $slug)
    {
        try {
            $sql = "SELECT * FROM plans WHERE slug = :slug";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['features'])) {
                $result['features'] = json_decode($result['features'], true);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Plan find by slug failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update plan data
     * 
     * @param string $id Plan ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['name', 'slug', 'description', 'price', 'currency', 'billing_period_months', 'features', 'status'];
            $updateFields = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    if ($key === 'features') {
                        $updateFields[] = "features = :features";
                        $params[':features'] = is_array($value) ? json_encode($value) : $value;
                    } else {
                        $updateFields[] = "$key = :$key";
                        $params[":$key"] = $value;
                    }
                }
            }

            if (empty($updateFields)) {
                return false;
            }

            $sql = "UPDATE plans SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Plan update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete plan
     * 
     * @param string $id Plan ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM plans WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Plan deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all plans with optional filters
     * 
     * @param array $filters Optional filters (status)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of plans
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM plans WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON features for each plan
            foreach ($results as &$result) {
                if (isset($result['features'])) {
                    $result['features'] = json_decode($result['features'], true);
                }
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log('Plan findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if slug exists
     * 
     * @param string $slug Slug to check
     * @param string|null $excludeId Optional plan ID to exclude from check
     * @return bool Returns true if slug exists, false otherwise
     */
    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM plans WHERE slug = :slug";
            $params = [':slug' => $slug];

            if ($excludeId !== null) {
                $sql .= " AND id != :id";
                $params[':id'] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Slug exists check failed: ' . $e->getMessage());
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
