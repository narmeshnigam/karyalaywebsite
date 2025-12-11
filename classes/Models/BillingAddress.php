<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;

/**
 * BillingAddress Model
 * Manages customer billing addresses (one per customer)
 */
class BillingAddress
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    /**
     * Generate UUID v4
     */
    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Get billing address by customer ID
     */
    public function findByCustomerId(string $customerId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM billing_addresses 
            WHERE customer_id = ?
        ");
        $stmt->execute([$customerId]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Create or update billing address for customer
     */
    public function createOrUpdate(string $customerId, array $data): bool
    {
        $existing = $this->findByCustomerId($customerId);
        
        if ($existing) {
            return $this->update($customerId, $data);
        } else {
            return $this->create($customerId, $data);
        }
    }

    /**
     * Create new billing address
     */
    private function create(string $customerId, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO billing_addresses (
                id, customer_id, full_name, business_name, business_tax_id,
                address_line1, address_line2, city, state, postal_code, 
                country, phone
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $this->uuid(),
            $customerId,
            $data['full_name'] ?? '',
            $data['business_name'] ?? null,
            $data['business_tax_id'] ?? null,
            $data['address_line1'] ?? '',
            $data['address_line2'] ?? null,
            $data['city'] ?? '',
            $data['state'] ?? '',
            $data['postal_code'] ?? '',
            $data['country'] ?? 'India',
            $data['phone'] ?? null
        ]);
    }

    /**
     * Update existing billing address
     */
    private function update(string $customerId, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE billing_addresses SET
                full_name = ?,
                business_name = ?,
                business_tax_id = ?,
                address_line1 = ?,
                address_line2 = ?,
                city = ?,
                state = ?,
                postal_code = ?,
                country = ?,
                phone = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE customer_id = ?
        ");

        return $stmt->execute([
            $data['full_name'] ?? '',
            $data['business_name'] ?? null,
            $data['business_tax_id'] ?? null,
            $data['address_line1'] ?? '',
            $data['address_line2'] ?? null,
            $data['city'] ?? '',
            $data['state'] ?? '',
            $data['postal_code'] ?? '',
            $data['country'] ?? 'India',
            $data['phone'] ?? null,
            $customerId
        ]);
    }

    /**
     * Delete billing address
     */
    public function delete(string $customerId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM billing_addresses WHERE customer_id = ?");
        return $stmt->execute([$customerId]);
    }
}
