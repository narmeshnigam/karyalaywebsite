<?php

namespace Karyalay\Services;

use Karyalay\Models\Module;
use Karyalay\Models\Solution;
use Karyalay\Models\Feature;
use Karyalay\Models\BlogPost;
use Karyalay\Models\CaseStudy;

/**
 * ContentService Class
 * 
 * Generic service layer for content CRUD operations with slug generation and validation
 */
class ContentService
{
    private const VALID_CONTENT_TYPES = ['module', 'solution', 'feature', 'blog_post', 'case_study'];
    private const VALID_STATUSES = ['DRAFT', 'PUBLISHED', 'ARCHIVED'];

    /**
     * Get model instance for content type
     * 
     * @param string $contentType Content type (module, feature, blog_post, case_study)
     * @return Module|Feature|BlogPost|CaseStudy
     * @throws \InvalidArgumentException If content type is invalid
     */
    private function getModel(string $contentType)
    {
        if (!in_array($contentType, self::VALID_CONTENT_TYPES)) {
            throw new \InvalidArgumentException("Invalid content type: $contentType");
        }

        return match ($contentType) {
            'module' => new Module(),
            'solution' => new Solution(),
            'feature' => new Feature(),
            'blog_post' => new BlogPost(),
            'case_study' => new CaseStudy(),
        };
    }

    /**
     * Create content entity
     * 
     * @param string $contentType Content type
     * @param array $data Content data
     * @return array|false Returns created content with id on success, false on failure
     */
    public function create(string $contentType, array $data)
    {
        $model = $this->getModel($contentType);

        // Generate slug if not provided
        if (!isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($contentType, $data['title'] ?? $data['name'] ?? '');
        } else {
            // Validate provided slug
            $data['slug'] = $this->sanitizeSlug($data['slug']);
        }

        // Validate slug uniqueness
        if (!$this->isSlugUnique($contentType, $data['slug'])) {
            error_log("Slug already exists: {$data['slug']}");
            return false;
        }

        // Validate status
        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES)) {
            error_log("Invalid status: {$data['status']}");
            return false;
        }

        return $model->create($data);
    }

    /**
     * Read content entity by ID
     * 
     * @param string $contentType Content type
     * @param string $id Content ID
     * @return array|false Returns content data or false if not found
     */
    public function read(string $contentType, string $id)
    {
        $model = $this->getModel($contentType);
        return $model->findById($id);
    }

    /**
     * Read content entity by slug
     * 
     * @param string $contentType Content type
     * @param string $slug Content slug
     * @return array|false Returns content data or false if not found
     */
    public function readBySlug(string $contentType, string $slug)
    {
        $model = $this->getModel($contentType);
        return $model->findBySlug($slug);
    }

    /**
     * Update content entity
     * 
     * @param string $contentType Content type
     * @param string $id Content ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $contentType, string $id, array $data): bool
    {
        $model = $this->getModel($contentType);

        // If slug is being updated, validate it
        if (isset($data['slug'])) {
            $data['slug'] = $this->sanitizeSlug($data['slug']);
            
            // Check slug uniqueness (excluding current entity)
            if (!$this->isSlugUnique($contentType, $data['slug'], $id)) {
                error_log("Slug already exists: {$data['slug']}");
                return false;
            }
        }

        // Validate status if provided
        if (isset($data['status']) && !in_array($data['status'], self::VALID_STATUSES)) {
            error_log("Invalid status: {$data['status']}");
            return false;
        }

        return $model->update($id, $data);
    }

    /**
     * Delete content entity
     * 
     * @param string $contentType Content type
     * @param string $id Content ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $contentType, string $id): bool
    {
        $model = $this->getModel($contentType);
        return $model->delete($id);
    }

    /**
     * Find all content entities with optional filters
     * 
     * @param string $contentType Content type
     * @param array $filters Optional filters (status, etc.)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of content entities
     */
    public function findAll(string $contentType, array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $model = $this->getModel($contentType);

        // Validate status filter if provided
        if (isset($filters['status']) && !in_array($filters['status'], self::VALID_STATUSES)) {
            error_log("Invalid status filter: {$filters['status']}");
            return [];
        }

        return $model->findAll($filters, $limit, $offset);
    }

    /**
     * Generate slug from text
     * 
     * @param string $contentType Content type
     * @param string $text Text to convert to slug
     * @return string Generated slug
     */
    public function generateSlug(string $contentType, string $text): string
    {
        $slug = $this->sanitizeSlug($text);

        // Ensure uniqueness by appending number if needed
        $originalSlug = $slug;
        $counter = 1;
        
        while (!$this->isSlugUnique($contentType, $slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Sanitize text to create a valid slug
     * 
     * @param string $text Text to sanitize
     * @return string Sanitized slug
     */
    private function sanitizeSlug(string $text): string
    {
        // Convert to lowercase
        $slug = strtolower($text);
        
        // Replace spaces and underscores with hyphens
        $slug = preg_replace('/[\s_]+/', '-', $slug);
        
        // Remove all non-alphanumeric characters except hyphens
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        
        // Remove multiple consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Trim hyphens from start and end
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Check if slug is unique for content type
     * 
     * @param string $contentType Content type
     * @param string $slug Slug to check
     * @param string|null $excludeId Optional ID to exclude from check
     * @return bool Returns true if slug is unique, false otherwise
     */
    private function isSlugUnique(string $contentType, string $slug, ?string $excludeId = null): bool
    {
        $model = $this->getModel($contentType);
        return !$model->slugExists($slug, $excludeId);
    }

    /**
     * Validate status value
     * 
     * @param string $status Status to validate
     * @return bool Returns true if status is valid, false otherwise
     */
    public function isValidStatus(string $status): bool
    {
        return in_array($status, self::VALID_STATUSES);
    }

    /**
     * Get all valid statuses
     * 
     * @return array Array of valid status values
     */
    public function getValidStatuses(): array
    {
        return self::VALID_STATUSES;
    }

    /**
     * Get all valid content types
     * 
     * @return array Array of valid content type values
     */
    public function getValidContentTypes(): array
    {
        return self::VALID_CONTENT_TYPES;
    }
}

