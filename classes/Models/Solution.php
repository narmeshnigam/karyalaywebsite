<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * Solution Model Class
 * 
 * Handles CRUD operations for solutions table
 */
class Solution
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new solution
     * 
     * @param array $data Solution data (name, slug, description, features, screenshots, faqs, display_order, status)
     * @return array|false Returns solution data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO solutions (
                id, name, slug, description, icon_image, features, benefits, screenshots, faqs, display_order, status, featured_on_homepage
            ) VALUES (
                :id, :name, :slug, :description, :icon_image, :features, :benefits, :screenshots, :faqs, :display_order, :status, :featured_on_homepage
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':description' => !empty($data['description']) ? $data['description'] : null,
                ':icon_image' => !empty($data['icon_image']) ? $data['icon_image'] : null,
                ':features' => isset($data['features']) ? json_encode($data['features']) : null,
                ':benefits' => isset($data['benefits']) ? json_encode($data['benefits']) : null,
                ':screenshots' => isset($data['screenshots']) ? json_encode($data['screenshots']) : null,
                ':faqs' => isset($data['faqs']) ? json_encode($data['faqs']) : null,
                ':display_order' => $data['display_order'] ?? 0,
                ':status' => $data['status'] ?? 'DRAFT',
                ':featured_on_homepage' => $data['featured_on_homepage'] ?? false
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Solution creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find solution by ID
     * 
     * @param string $id Solution ID
     * @return array|false Returns solution data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM solutions WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Solution find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find solution by slug
     * 
     * @param string $slug Solution slug
     * @return array|false Returns solution data or false if not found
     */
    public function findBySlug(string $slug)
    {
        try {
            $sql = "SELECT * FROM solutions WHERE slug = :slug";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Solution find by slug failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update solution data
     * 
     * @param string $id Solution ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['name', 'slug', 'description', 'icon_image', 'features', 'benefits', 'screenshots', 'faqs', 'display_order', 'status', 'featured_on_homepage'];
            $updateFields = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    if (in_array($key, ['features', 'benefits', 'screenshots', 'faqs'])) {
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

            $sql = "UPDATE solutions SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('Solution update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete solution
     * 
     * @param string $id Solution ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM solutions WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Solution deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all solutions with optional filters
     * 
     * @param array $filters Optional filters (status)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of solutions
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM solutions WHERE 1=1";
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
            
            foreach ($results as &$result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log('Solution findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get featured solutions for homepage
     * 
     * @param int $limit Maximum number of solutions to return
     * @return array Returns array of featured solutions
     */
    public function getFeaturedSolutions(int $limit = 6): array
    {
        try {
            $sql = "SELECT * FROM solutions 
                    WHERE status = 'PUBLISHED' AND featured_on_homepage = TRUE 
                    ORDER BY display_order ASC, created_at DESC 
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as &$result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log('Featured solutions fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if slug exists
     * 
     * @param string $slug Slug to check
     * @param string|null $excludeId Optional solution ID to exclude from check
     * @return bool Returns true if slug exists, false otherwise
     */
    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM solutions WHERE slug = :slug";
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

    private function decodeJsonFields(array $data): array
    {
        $jsonFields = ['features', 'benefits', 'screenshots', 'faqs', 'use_cases', 'stats'];
        
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = json_decode($data[$field], true);
            }
        }
        
        return $data;
    }

    /**
     * Get linked features for a solution
     * 
     * @param string $solutionId Solution ID
     * @return array Returns array of linked features with full details
     */
    public function getLinkedFeatures(string $solutionId): array
    {
        try {
            $sql = "SELECT f.*, sf.display_order as link_order, sf.is_highlighted
                    FROM features f
                    INNER JOIN solution_features sf ON f.id = sf.feature_id
                    WHERE sf.solution_id = :solution_id AND f.status = 'PUBLISHED'
                    ORDER BY sf.display_order ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':solution_id' => $solutionId]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as &$result) {
                // Decode JSON fields for features
                $jsonFields = ['benefits', 'related_solutions', 'screenshots'];
                foreach ($jsonFields as $field) {
                    if (isset($result[$field]) && is_string($result[$field])) {
                        $result[$field] = json_decode($result[$field], true);
                    }
                }
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log('Get linked features failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get related solutions (solutions that share features with this one)
     * 
     * @param string $solutionId Solution ID
     * @param int $limit Maximum number of related solutions
     * @return array Returns array of related solutions
     */
    public function getRelatedSolutions(string $solutionId, int $limit = 3): array
    {
        try {
            $sql = "SELECT DISTINCT s.*, COUNT(sf2.feature_id) as shared_features
                    FROM solutions s
                    INNER JOIN solution_features sf2 ON s.id = sf2.solution_id
                    WHERE sf2.feature_id IN (
                        SELECT feature_id FROM solution_features WHERE solution_id = :solution_id
                    )
                    AND s.id != :solution_id2
                    AND s.status = 'PUBLISHED'
                    GROUP BY s.id
                    ORDER BY shared_features DESC, s.display_order ASC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':solution_id', $solutionId);
            $stmt->bindValue(':solution_id2', $solutionId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as &$result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log('Get related solutions failed: ' . $e->getMessage());
            return [];
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
