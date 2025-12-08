<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * BlogPost Model Class
 * 
 * Handles CRUD operations for blog_posts table
 */
class BlogPost
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new blog post
     * 
     * @param array $data Blog post data (title, slug, content, excerpt, author_id, tags, status, published_at)
     * @return array|false Returns blog post data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO blog_posts (
                id, title, slug, content, excerpt, author_id, tags, status, is_featured, published_at
            ) VALUES (
                :id, :title, :slug, :content, :excerpt, :author_id, :tags, :status, :is_featured, :published_at
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':title' => $data['title'],
                ':slug' => $data['slug'],
                ':content' => $data['content'],
                ':excerpt' => $data['excerpt'] ?? null,
                ':author_id' => $data['author_id'],
                ':tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
                ':status' => $data['status'] ?? 'DRAFT',
                ':is_featured' => $data['is_featured'] ?? false,
                ':published_at' => $data['published_at'] ?? null
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('BlogPost creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find blog post by ID
     * 
     * @param string $id Blog post ID
     * @return array|false Returns blog post data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM blog_posts WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('BlogPost find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find blog post by slug
     * 
     * @param string $slug Blog post slug
     * @return array|false Returns blog post data or false if not found
     */
    public function findBySlug(string $slug)
    {
        try {
            $sql = "SELECT * FROM blog_posts WHERE slug = :slug";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':slug' => $slug]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('BlogPost find by slug failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find blog posts by author ID
     * 
     * @param string $authorId Author ID
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of blog posts
     */
    public function findByAuthorId(string $authorId, int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM blog_posts WHERE author_id = :author_id ORDER BY published_at DESC, created_at DESC LIMIT :limit OFFSET :offset";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':author_id', $authorId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON fields for each blog post
            foreach ($results as &$result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log('BlogPost find by author ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update blog post data
     * 
     * @param string $id Blog post ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['title', 'slug', 'content', 'excerpt', 'author_id', 'tags', 'status', 'is_featured', 'published_at'];
            $updateFields = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    if ($key === 'tags') {
                        $updateFields[] = "tags = :tags";
                        $params[':tags'] = is_array($value) ? json_encode($value) : $value;
                    } else {
                        $updateFields[] = "$key = :$key";
                        $params[":$key"] = $value;
                    }
                }
            }

            if (empty($updateFields)) {
                return false;
            }

            $sql = "UPDATE blog_posts SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('BlogPost update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete blog post
     * 
     * @param string $id Blog post ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM blog_posts WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('BlogPost deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get featured blog posts for homepage
     * 
     * @param int $limit Maximum number of blog posts to return
     * @return array Returns array of featured blog posts
     */
    public function getFeatured(int $limit = 3): array
    {
        try {
            $sql = "SELECT * FROM blog_posts 
                    WHERE status = 'PUBLISHED' AND is_featured = TRUE 
                    ORDER BY published_at DESC, created_at DESC 
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
            error_log('Featured blog posts fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all blog posts with optional filters
     * 
     * @param array $filters Optional filters (status, author_id)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of blog posts
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM blog_posts WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['author_id'])) {
                $sql .= " AND author_id = :author_id";
                $params[':author_id'] = $filters['author_id'];
            }

            $sql .= " ORDER BY published_at DESC, created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON fields for each blog post
            foreach ($results as &$result) {
                $result = $this->decodeJsonFields($result);
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log('BlogPost findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if slug exists
     * 
     * @param string $slug Slug to check
     * @param string|null $excludeId Optional blog post ID to exclude from check
     * @return bool Returns true if slug exists, false otherwise
     */
    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM blog_posts WHERE slug = :slug";
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
     * Publish blog post
     * 
     * @param string $id Blog post ID
     * @return bool Returns true on success, false on failure
     */
    public function publish(string $id): bool
    {
        return $this->update($id, [
            'status' => 'PUBLISHED',
            'published_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Unpublish blog post
     * 
     * @param string $id Blog post ID
     * @return bool Returns true on success, false on failure
     */
    public function unpublish(string $id): bool
    {
        return $this->update($id, [
            'status' => 'DRAFT',
            'published_at' => null
        ]);
    }

    /**
     * Decode JSON fields in blog post data
     * 
     * @param array $data Blog post data
     * @return array Blog post data with decoded JSON fields
     */
    private function decodeJsonFields(array $data): array
    {
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = json_decode($data['tags'], true);
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
