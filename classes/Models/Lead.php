<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

class Lead
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();

            // Check if column is 'company' or 'company_name'
            $columns = $this->db->query("SHOW COLUMNS FROM leads LIKE 'company%'")->fetchAll(PDO::FETCH_COLUMN);
            $companyColumn = in_array('company_name', $columns) ? 'company_name' : 'company';
            
            $sql = "INSERT INTO leads (
                id, name, email, phone, {$companyColumn}, message, source, status
            ) VALUES (
                :id, :name, :email, :phone, :company, :message, :source, :status
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':phone' => !empty($data['phone']) ? $data['phone'] : null,
                ':company' => !empty($data['company']) ? $data['company'] : null,
                ':message' => !empty($data['message']) ? $data['message'] : null,
                ':source' => $data['source'] ?? 'CONTACT_FORM',
                ':status' => 'NEW'
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Lead creation failed: ' . $e->getMessage());
            error_log('SQL Error Code: ' . $e->getCode());
            return false;
        }
    }

    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM leads WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (PDOException $e) {
            error_log('Lead find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM leads WHERE 1=1";
            $params = [];

            if (isset($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Leads getAll failed: ' . $e->getMessage());
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
