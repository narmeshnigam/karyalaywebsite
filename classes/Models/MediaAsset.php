<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * MediaAsset Model Class
 * 
 * Handles CRUD operations for media_assets table
 */
class MediaAsset
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new media asset
     * 
     * @param array $data Media asset data (filename, url, file_path, mime_type, size, uploaded_by)
     * @return array|false Returns media asset data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO media_assets (
                id, filename, url, file_path, mime_type, size, uploaded_by
            ) VALUES (
                :id, :filename, :url, :file_path, :mime_type, :size, :uploaded_by
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':filename' => $data['filename'],
                ':url' => $data['url'],
                ':file_path' => $data['file_path'] ?? null,
                ':mime_type' => $data['mime_type'],
                ':size' => $data['size'],
                ':uploaded_by' => $data['uploaded_by']
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('MediaAsset creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find media asset by ID
     * 
     * @param string $id Media asset ID
     * @return array|false Returns media asset data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM media_assets WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('MediaAsset find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update media asset data
     * 
     * @param string $id Media asset ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['filename', 'url', 'file_path', 'mime_type', 'size'];
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

            $sql = "UPDATE media_assets SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('MediaAsset update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete media asset
     * 
     * @param string $id Media asset ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM media_assets WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('MediaAsset deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all media assets with optional filters
     * 
     * @param array $filters Optional filters (uploaded_by, mime_type)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of media assets
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM media_assets WHERE 1=1";
            $params = [];

            if (isset($filters['uploaded_by'])) {
                $sql .= " AND uploaded_by = :uploaded_by";
                $params[':uploaded_by'] = $filters['uploaded_by'];
            }

            if (isset($filters['mime_type'])) {
                $sql .= " AND mime_type = :mime_type";
                $params[':mime_type'] = $filters['mime_type'];
            }

            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('MediaAsset findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find media assets by uploader
     * 
     * @param string $uploadedBy User ID who uploaded the assets
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of media assets
     */
    public function findByUploader(string $uploadedBy, int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['uploaded_by' => $uploadedBy], $limit, $offset);
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
