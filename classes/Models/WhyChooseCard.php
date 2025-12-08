<?php
/**
 * WhyChooseCard Model
 * Handles "Why Choose" section cards for the home page
 */

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;

class WhyChooseCard
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Get published cards ordered by display_order (max 6)
     */
    public function getPublishedCards(int $limit = 6): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM why_choose_cards WHERE status = 'PUBLISHED' ORDER BY display_order ASC LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all cards with optional filters
     */
    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT * FROM why_choose_cards WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY display_order ASC, created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count cards with optional filters
     */
    public function count(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM why_choose_cards WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get a single card by ID
     */
    public function getById(string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM why_choose_cards WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Create a new card
     */
    public function create(array $data): ?string
    {
        $id = $this->generateUuid();
        
        $stmt = $this->db->prepare(
            "INSERT INTO why_choose_cards (id, title, description, image_url, link_url, display_order, status)
             VALUES (:id, :title, :description, :image_url, :link_url, :display_order, :status)"
        );

        $result = $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':image_url' => $data['image_url'],
            ':link_url' => $data['link_url'] ?? null,
            ':display_order' => $data['display_order'] ?? 0,
            ':status' => $data['status'] ?? 'DRAFT'
        ]);

        return $result ? $id : null;
    }

    /**
     * Update an existing card
     */
    public function update(string $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE why_choose_cards SET 
                title = :title,
                description = :description,
                image_url = :image_url,
                link_url = :link_url,
                display_order = :display_order,
                status = :status
             WHERE id = :id"
        );

        return $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':image_url' => $data['image_url'],
            ':link_url' => $data['link_url'] ?? null,
            ':display_order' => $data['display_order'] ?? 0,
            ':status' => $data['status'] ?? 'DRAFT'
        ]);
    }

    /**
     * Delete a card
     */
    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM why_choose_cards WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Generate UUID v4
     */
    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
