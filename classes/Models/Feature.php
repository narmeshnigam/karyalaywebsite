<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * Feature Model Class
 * 
 * Handles CRUD operations for features table
 */
class Feature
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new feature
     * 
     * @param array $data Feature data (name, slug, description, benefits, related_modules, screenshots, display_order, status)
     * @return array|false Returns feature data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO features (
                id, name, slug, description, icon_image, benefits, related_solutions, screenshots, display_order, status
            ) VALUES (
                :id, :name, :slug, :description, :icon_image, :benefits, :related_solutions, :screenshots, :display_order, :status
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':description' => !empty($data['description']) ? $data['description'] : null,
                ':icon_image' => !empty($data['icon_image']) ? $data['icon_image'] : null,
                ':benefits' => isset($data['benefits']) ? json_encode($data['benefits']) : null,
                ':related_solutions' => isset($data['related_solutions']) ? json_encode($data['related_solutions']) : null,
                ':screenshots' => isset($data['screenshots']) ? json_encode($data['screenshots']) : null,
                ':display_order' => $data['display_order'] ?? 0,
                ':status' => $data['status'] ?? 'DRAFT'
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Feature creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find feature by ID
     * 
     * @param string $id Feature ID
     * @return array|false Returns feature data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM features WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Feature find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find feature by slug
     * 
     * @param string $slug Feature slug
     * @return array|false Returns feature data or false if not found
     */
    public function findBySlug(string $slug)
    {
        try {
            $sql = "SELECT * FROM features WHERE slug = :slug";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Feature find by slug failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update feature data
     * 
     * @param string $id Feature ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['name', 'slug', 'description', 'icon_image', 'benefits', 'related_solutions', 'screenshots', 'display_order', 'status'];
            $updateFields = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    if (in_array($key, ['benefits', 'related_solutions', 'screenshots'])) {
                        $updateFields[] = "$key = :$key";
                        $params[":$key"] = is_array($value) ? json_encode($value) : $value;
                    } else {
                        $updateFields[] = "$key = :$key";
                        // Convert empty strings to NULL for optional fields
                        if (in_array($key, ['description', 'icon_image']) && $value === '') {
                            $params[":$key"] = null;
                        } else {
                            $params[":$key"] = $value;
                        }
                    }
                }
            }

            if (empty($updateFields)) {
                return false;
            }

            $sql = "UPDATE features SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Feature update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete feature
     * 
     * @param string $id Feature ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM features WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Feature deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all features with optional filters
     * 
     * @param array $filters Optional filters (status)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of features
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM features WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            $sql .= " ORDER BY display_order ASC, created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON fields for each feature
            foreach ($results as &$result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log('Feature findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if slug exists
     * 
     * @param string $slug Slug to check
     * @param string|null $excludeId Optional feature ID to exclude from check
     * @return bool Returns true if slug exists, false otherwise
     */
    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM features WHERE slug = :slug";
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
     * Decode JSON fields in feature data
     * 
     * @param array $data Feature data
     * @return array Feature data with decoded JSON fields
     */
    private function decodeJsonFields(array $data): array
    {
        $jsonFields = ['benefits', 'related_solutions', 'screenshots'];
        
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        return $data;
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
