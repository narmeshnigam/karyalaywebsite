<?php

namespace Karyalay\Services;

use Karyalay\Models\Plan;

/**
 * PlanService Class
 * 
 * Service layer for plan CRUD operations with status filtering and validation
 */
class PlanService
{
    private const VALID_STATUSES = ['ACTIVE', 'INACTIVE'];
    private Plan $planModel;

    public function __construct()
    {
        $this->planModel = new Plan();
    }

    /**
     * Create a new plan
     * 
     * @param array $data Plan data (name, slug, description, price, currency, billing_period_months, features, status, number_of_users, allowed_storage_gb, mrp, discounted_price, features_html)
     * @return array|false Returns created plan with id on success, false on failure
     */
    public function create(array $data)
    {
        // Validate required fields
        if (!$this->validateRequiredFields($data)) {
            error_log("Plan creation failed: Missing required fields");
            return false;
        }

        // Generate slug if not provided
        if (!isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        } else {
            // Sanitize provided slug
            $data['slug'] = $this->sanitizeSlug($data['slug']);
        }

        // Validate slug uniqueness
        if ($this->planModel->slugExists($data['slug'])) {
            error_log("Plan creation failed: Slug already exists - {$data['slug']}");
            return false;
        }

        // Validate status
        if (isset($data['status']) && !$this->isValidStatus($data['status'])) {
            error_log("Plan creation failed: Invalid status - {$data['status']}");
            return false;
        }

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'ACTIVE';
        }

        // Validate billing period
        if (isset($data['billing_period_months']) && (!is_numeric($data['billing_period_months']) || $data['billing_period_months'] <= 0)) {
            error_log("Plan creation failed: Invalid billing period");
            return false;
        }

        // Validate MRP (required)
        if (!isset($data['mrp']) || $data['mrp'] === '' || !is_numeric($data['mrp']) || $data['mrp'] < 0) {
            error_log("Plan creation failed: Invalid or missing MRP");
            return false;
        }

        // Validate discounted price if provided
        if (isset($data['discounted_price']) && $data['discounted_price'] !== '' && (!is_numeric($data['discounted_price']) || $data['discounted_price'] < 0)) {
            error_log("Plan creation failed: Invalid discounted price");
            return false;
        }

        // Validate number of users if provided
        if (isset($data['number_of_users']) && $data['number_of_users'] !== '' && (!is_numeric($data['number_of_users']) || $data['number_of_users'] < 0)) {
            error_log("Plan creation failed: Invalid number of users");
            return false;
        }

        // Validate allowed storage if provided
        if (isset($data['allowed_storage_gb']) && $data['allowed_storage_gb'] !== '' && (!is_numeric($data['allowed_storage_gb']) || $data['allowed_storage_gb'] < 0)) {
            error_log("Plan creation failed: Invalid allowed storage");
            return false;
        }

        return $this->planModel->create($data);
    }

    /**
     * Read plan by ID
     * 
     * @param string $id Plan ID
     * @return array|false Returns plan data or false if not found
     */
    public function read(string $id)
    {
        return $this->planModel->findById($id);
    }

    /**
     * Read plan by slug
     * 
     * @param string $slug Plan slug
     * @return array|false Returns plan data or false if not found
     */
    public function readBySlug(string $slug)
    {
        return $this->planModel->findBySlug($slug);
    }

    /**
     * Update plan
     * 
     * @param string $id Plan ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        // If slug is being updated, validate it
        if (isset($data['slug'])) {
            $data['slug'] = $this->sanitizeSlug($data['slug']);
            
            // Check slug uniqueness (excluding current plan)
            if ($this->planModel->slugExists($data['slug'], $id)) {
                error_log("Plan update failed: Slug already exists - {$data['slug']}");
                return false;
            }
        }

        // Validate status if provided
        if (isset($data['status']) && !$this->isValidStatus($data['status'])) {
            error_log("Plan update failed: Invalid status - {$data['status']}");
            return false;
        }

        // Validate billing period if provided
        if (isset($data['billing_period_months']) && (!is_numeric($data['billing_period_months']) || $data['billing_period_months'] <= 0)) {
            error_log("Plan update failed: Invalid billing period");
            return false;
        }

        // Validate MRP if provided (must be valid if present)
        if (isset($data['mrp']) && $data['mrp'] !== '' && $data['mrp'] !== null && (!is_numeric($data['mrp']) || $data['mrp'] < 0)) {
            error_log("Plan update failed: Invalid MRP");
            return false;
        }

        // Validate discounted price if provided
        if (isset($data['discounted_price']) && $data['discounted_price'] !== '' && $data['discounted_price'] !== null && (!is_numeric($data['discounted_price']) || $data['discounted_price'] < 0)) {
            error_log("Plan update failed: Invalid discounted price");
            return false;
        }

        // Validate number of users if provided
        if (isset($data['number_of_users']) && $data['number_of_users'] !== '' && $data['number_of_users'] !== null && (!is_numeric($data['number_of_users']) || $data['number_of_users'] < 0)) {
            error_log("Plan update failed: Invalid number of users");
            return false;
        }

        // Validate allowed storage if provided
        if (isset($data['allowed_storage_gb']) && $data['allowed_storage_gb'] !== '' && $data['allowed_storage_gb'] !== null && (!is_numeric($data['allowed_storage_gb']) || $data['allowed_storage_gb'] < 0)) {
            error_log("Plan update failed: Invalid allowed storage");
            return false;
        }

        return $this->planModel->update($id, $data);
    }

    /**
     * Delete plan
     * 
     * @param string $id Plan ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        return $this->planModel->delete($id);
    }

    /**
     * Find all plans with optional filters
     * 
     * @param array $filters Optional filters (status, billing_period_months, duration)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of plans
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        // Validate status filter if provided
        if (isset($filters['status']) && !$this->isValidStatus($filters['status'])) {
            error_log("Invalid status filter: {$filters['status']}");
            return [];
        }

        // Validate duration filter if provided
        $validDurations = ['all', 'monthly', 'quarterly', 'annual'];
        if (isset($filters['duration']) && !in_array($filters['duration'], $validDurations)) {
            error_log("Invalid duration filter: {$filters['duration']}");
            return [];
        }

        return $this->planModel->findAll($filters, $limit, $offset);
    }

    /**
     * Get plans grouped by duration
     * 
     * @return array Returns array of plans grouped by duration category
     */
    public function getPlansByDuration(): array
    {
        $allPlans = $this->getActivePlans();
        
        $grouped = [
            'monthly' => [],
            'quarterly' => [],
            'annual' => [],
            'other' => []
        ];

        foreach ($allPlans as $plan) {
            $months = (int) $plan['billing_period_months'];
            if ($months === 1) {
                $grouped['monthly'][] = $plan;
            } elseif ($months === 3) {
                $grouped['quarterly'][] = $plan;
            } elseif ($months === 12) {
                $grouped['annual'][] = $plan;
            } else {
                $grouped['other'][] = $plan;
            }
        }

        return $grouped;
    }

    /**
     * Get the effective price for a plan (discounted if available, otherwise MRP)
     * 
     * @param array $plan Plan data
     * @return float The effective price
     */
    public function getEffectivePrice(array $plan): float
    {
        if (!empty($plan['discounted_price']) && $plan['discounted_price'] > 0) {
            return (float) $plan['discounted_price'];
        }
        return (float) ($plan['mrp'] ?? 0);
    }

    /**
     * Check if plan has a discount
     * 
     * @param array $plan Plan data
     * @return bool True if plan has discount
     */
    public function hasDiscount(array $plan): bool
    {
        return !empty($plan['mrp']) && !empty($plan['discounted_price']) 
            && $plan['mrp'] > $plan['discounted_price'];
    }

    /**
     * Get discount percentage
     * 
     * @param array $plan Plan data
     * @return float Discount percentage (0-100)
     */
    public function getDiscountPercentage(array $plan): float
    {
        if (!$this->hasDiscount($plan)) {
            return 0;
        }
        return round((($plan['mrp'] - $plan['discounted_price']) / $plan['mrp']) * 100, 1);
    }

    /**
     * Get active plans only
     * 
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of active plans
     */
    public function getActivePlans(int $limit = 100, int $offset = 0): array
    {
        return $this->findAll(['status' => 'ACTIVE'], $limit, $offset);
    }

    /**
     * Generate slug from plan name
     * 
     * @param string $name Plan name
     * @return string Generated slug
     */
    public function generateSlug(string $name): string
    {
        $slug = $this->sanitizeSlug($name);

        // Ensure uniqueness by appending number if needed
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->planModel->slugExists($slug)) {
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
     * Validate required fields for plan creation
     * 
     * @param array $data Plan data
     * @return bool Returns true if all required fields are present, false otherwise
     */
    private function validateRequiredFields(array $data): bool
    {
        $requiredFields = ['name', 'mrp', 'billing_period_months'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                return false;
            }
        }
        
        return true;
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
     * Check if slug exists
     * 
     * @param string $slug Slug to check
     * @param string|null $excludeId Optional plan ID to exclude from check
     * @return bool Returns true if slug exists, false otherwise
     */
    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        return $this->planModel->slugExists($slug, $excludeId);
    }
}
