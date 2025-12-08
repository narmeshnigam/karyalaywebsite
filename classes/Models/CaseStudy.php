<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * CaseStudy Model Class
 * 
 * Handles CRUD operations for case_studies table
 */
class CaseStudy
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new case study
     * 
     * @param array $data Case study data (title, slug, client_name, industry, challenge, solution, results, modules_used, status)
     * @return array|false Returns case study data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO case_studies (
                id, title, slug, client_name, industry, challenge, cover_image, solution, results, solutions_used, status, is_featured
            ) VALUES (
                :id, :title, :slug, :client_name, :industry, :challenge, :cover_image, :solution, :results, :solutions_used, :status, :is_featured
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':title' => $data['title'],
                ':slug' => $data['slug'],
                ':client_name' => $data['client_name'],
                ':industry' => $data['industry'] ?? null,
                ':challenge' => $data['challenge'] ?? null,
                ':cover_image' => !empty($data['cover_image']) ? $data['cover_image'] : null,
                ':solution' => $data['solution'] ?? null,
                ':results' => $data['results'] ?? null,
                ':solutions_used' => isset($data['modules_used']) ? json_encode($data['modules_used']) : null,
                ':status' => $data['status'] ?? 'DRAFT',
                ':is_featured' => $data['is_featured'] ?? false
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('CaseStudy creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find case study by ID
     * 
     * @param string $id Case study ID
     * @return array|false Returns case study data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM case_studies WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('CaseStudy find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find case study by slug
     * 
     * @param string $slug Case study slug
     * @return array|false Returns case study data or false if not found
     */
    public function findBySlug(string $slug)
    {
        try {
            $sql = "SELECT * FROM case_studies WHERE slug = :slug";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('CaseStudy find by slug failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update case study data
     * 
     * @param string $id Case study ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['title', 'slug', 'client_name', 'industry', 'challenge', 'cover_image', 'solution', 'results', 'modules_used', 'status', 'is_featured'];
            $updateFields = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    if ($key === 'modules_used') {
                        $updateFields[] = "solutions_used = :solutions_used";
                        $params[':solutions_used'] = is_array($value) ? json_encode($value) : $value;
                    } else {
                        $updateFields[] = "$key = :$key";
                        if ($key === 'cover_image' && $value === '') {
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

            $sql = "UPDATE case_studies SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('CaseStudy update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete case study
     * 
     * @param string $id Case study ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM case_studies WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('CaseStudy deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all case studies with optional filters
     * 
     * @param array $filters Optional filters (status, industry)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of case studies
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM case_studies WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['industry'])) {
                $sql .= " AND industry = :industry";
                $params[':industry'] = $filters['industry'];
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
            
            // Decode JSON fields for each case study
            foreach ($results as &$result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log('CaseStudy findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get featured case studies for homepage
     * 
     * @param int $limit Maximum number of case studies to return
     * @return array Returns array of featured case studies
     */
    public function getFeatured(int $limit = 3): array
    {
        try {
            $sql = "SELECT * FROM case_studies 
                    WHERE status = 'PUBLISHED' AND is_featured = TRUE 
                    ORDER BY created_at DESC 
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
            error_log('Featured case studies fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if slug exists
     * 
     * @param string $slug Slug to check
     * @param string|null $excludeId Optional case study ID to exclude from check
     * @return bool Returns true if slug exists, false otherwise
     */
    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM case_studies WHERE slug = :slug";
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
     * Decode JSON fields in case study data
     * 
     * @param array $data Case study data
     * @return array Case study data with decoded JSON fields
     */
    private function decodeJsonFields(array $data): array
    {
        // Map solutions_used from DB to modules_used for backward compatibility
        if (isset($data['solutions_used']) && is_string($data['solutions_used'])) {
            $data['modules_used'] = json_decode($data['solutions_used'], true);
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
