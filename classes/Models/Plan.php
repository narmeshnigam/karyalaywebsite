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
     * @param array $data Plan data (name, slug, description, currency, billing_period_months, status, number_of_users, allowed_storage_gb, mrp, discounted_price, features_html, discount_amount, net_price, tax_percent, tax_name, tax_description, tax_amount)
     * @return array|false Returns plan data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            $sql = "INSERT INTO plans (
                id, name, slug, description, currency, billing_period_months, status,
                number_of_users, allowed_storage_gb, mrp, discounted_price, features_html,
                discount_amount, net_price, tax_percent, tax_name, tax_description, tax_amount
            ) VALUES (
                :id, :name, :slug, :description, :currency, :billing_period_months, :status,
                :number_of_users, :allowed_storage_gb, :mrp, :discounted_price, :features_html,
                :discount_amount, :net_price, :tax_percent, :tax_name, :tax_description, :tax_amount
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':currency' => $data['currency'] ?? 'USD',
                ':billing_period_months' => $data['billing_period_months'],
                ':status' => $data['status'] ?? 'ACTIVE',
                ':number_of_users' => $data['number_of_users'] ?? null,
                ':allowed_storage_gb' => $data['allowed_storage_gb'] ?? null,
                ':mrp' => $data['mrp'],
                ':discounted_price' => $data['discounted_price'] ?? null,
                ':features_html' => $data['features_html'] ?? null,
                ':discount_amount' => $data['discount_amount'] ?? null,
                ':net_price' => $data['net_price'] ?? null,
                ':tax_percent' => $data['tax_percent'] ?? null,
                ':tax_name' => $data['tax_name'] ?? null,
                ':tax_description' => $data['tax_description'] ?? null,
                ':tax_amount' => $data['tax_amount'] ?? null
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
            $allowedFields = ['name', 'slug', 'description', 'currency', 'billing_period_months', 'status', 'number_of_users', 'allowed_storage_gb', 'mrp', 'discounted_price', 'features_html', 'discount_amount', 'net_price', 'tax_percent', 'tax_name', 'tax_description', 'tax_amount'];
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
     * @param array $filters Optional filters (status, billing_period_months, duration)
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

            // Filter by billing period (duration)
            if (isset($filters['billing_period_months'])) {
                $sql .= " AND billing_period_months = :billing_period_months";
                $params[':billing_period_months'] = $filters['billing_period_months'];
            }

            // Filter by duration category
            if (isset($filters['duration'])) {
                switch ($filters['duration']) {
                    case 'monthly':
                        $sql .= " AND billing_period_months = 1";
                        break;
                    case 'quarterly':
                        $sql .= " AND billing_period_months = 3";
                        break;
                    case 'annual':
                        $sql .= " AND billing_period_months = 12";
                        break;
                    // 'all' or default - no filter
                }
            }

            $sql .= " ORDER BY billing_period_months ASC, mrp ASC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
     * Get the selling price for a plan (discounted_price if set, otherwise mrp)
     * 
     * @param array $plan Plan data
     * @return float Selling price
     */
    public static function getSellingPrice(array $plan): float
    {
        if (!empty($plan['discounted_price']) && $plan['discounted_price'] > 0) {
            return (float)$plan['discounted_price'];
        }
        return (float)$plan['mrp'];
    }

    /**
     * Calculate tax breakdown from selling price
     * 
     * @param float $sellingPrice The final selling price (tax inclusive)
     * @param float $taxPercent Tax percentage
     * @return array ['net_price' => float, 'tax_amount' => float]
     */
    public static function calculateTaxBreakdown(float $sellingPrice, float $taxPercent): array
    {
        if ($taxPercent <= 0) {
            return [
                'net_price' => $sellingPrice,
                'tax_amount' => 0
            ];
        }
        
        // Calculate net price from tax-inclusive price
        // selling_price = net_price + (net_price * tax_percent / 100)
        // selling_price = net_price * (1 + tax_percent / 100)
        // net_price = selling_price / (1 + tax_percent / 100)
        $netPrice = $sellingPrice / (1 + $taxPercent / 100);
        $taxAmount = $sellingPrice - $netPrice;
        
        return [
            'net_price' => round($netPrice, 2),
            'tax_amount' => round($taxAmount, 2)
        ];
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
