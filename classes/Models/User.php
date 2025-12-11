<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;

/**
 * User Model Class
 * 
 * Handles CRUD operations for users table
 */
class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new user
     * 
     * @param array $data User data (email, password, name, phone, business_name, role)
     * @return array|false Returns user data with id on success, false on failure
     */
    public function create(array $data)
    {
        try {
            $id = $this->generateUuid();
            
            // Hash password if provided as plain text
            $passwordHash = isset($data['password']) 
                ? password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12])
                : $data['password_hash'];

            $role = $data['role'] ?? 'CUSTOMER';

            $sql = "INSERT INTO users (
                id, email, password_hash, name, phone, business_name, role, email_verified
            ) VALUES (
                :id, :email, :password_hash, :name, :phone, :business_name, :role, :email_verified
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':email' => $data['email'],
                ':password_hash' => $passwordHash,
                ':name' => $data['name'],
                ':phone' => $data['phone'] ?? null,
                ':business_name' => $data['business_name'] ?? null,
                ':role' => $role,
                ':email_verified' => isset($data['email_verified']) ? (int)$data['email_verified'] : 0
            ]);

            // Add roles to user_roles table
            // All users get CUSTOMER role by default
            $roles = ['CUSTOMER'];
            if ($role !== 'CUSTOMER') {
                $roles[] = $role;
            }
            
            try {
                $roleStmt = $this->db->prepare("INSERT INTO user_roles (user_id, role) VALUES (:user_id, :role) ON DUPLICATE KEY UPDATE role = VALUES(role)");
                foreach ($roles as $r) {
                    $roleStmt->execute([':user_id' => $id, ':role' => $r]);
                }
            } catch (PDOException $e) {
                // user_roles table might not exist yet, log but don't fail
                error_log('Could not add user roles (table may not exist): ' . $e->getMessage());
            }

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('User creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find user by ID
     * 
     * @param string $id User ID
     * @return array|false Returns user data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('User find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find user by email
     * 
     * @param string $email User email
     * @return array|false Returns user data or false if not found
     */
    public function findByEmail(string $email)
    {
        try {
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('User find by email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user data
     * 
     * @param string $id User ID
     * @param array $data Data to update
     * @return bool Returns true on success, false on failure
     */
    public function update(string $id, array $data): bool
    {
        try {
            $allowedFields = ['email', 'password', 'password_hash', 'name', 'phone', 'business_name', 'role', 'email_verified'];
            $updateFields = [];
            $params = [':id' => $id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    // Hash password if updating password
                    if ($key === 'password') {
                        $updateFields[] = "password_hash = :password_hash";
                        $params[':password_hash'] = password_hash($value, PASSWORD_BCRYPT, ['cost' => 12]);
                    } else {
                        $updateFields[] = "$key = :$key";
                        $params[":$key"] = $value;
                    }
                }
            }

            if (empty($updateFields)) {
                return false;
            }

            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('User update failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all roles for a user
     * 
     * @param string $id User ID
     * @return array Array of role names
     */
    public function getRoles(string $id): array
    {
        try {
            $sql = "SELECT role FROM user_roles WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $id]);
            $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Fallback to users.role if user_roles is empty
            if (empty($roles)) {
                $user = $this->findById($id);
                if ($user && !empty($user['role'])) {
                    $roles = [$user['role']];
                    if ($user['role'] !== 'CUSTOMER') {
                        $roles[] = 'CUSTOMER';
                    }
                }
            }
            
            return $roles;
        } catch (PDOException $e) {
            error_log('Get user roles failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Set roles for a user (replaces existing roles)
     * 
     * @param string $id User ID
     * @param array $roles Array of role names
     * @param string|null $assignedBy User ID of who assigned the roles
     * @return bool Returns true on success, false on failure
     */
    public function setRoles(string $id, array $roles, ?string $assignedBy = null): bool
    {
        try {
            // Ensure CUSTOMER role is always included
            if (!in_array('CUSTOMER', $roles)) {
                $roles[] = 'CUSTOMER';
            }
            
            $this->db->beginTransaction();
            
            // Remove all existing roles
            $stmt = $this->db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $id]);
            
            // Add new roles
            $stmt = $this->db->prepare("
                INSERT INTO user_roles (user_id, role, assigned_by)
                VALUES (:user_id, :role, :assigned_by)
            ");
            
            foreach ($roles as $role) {
                $stmt->execute([
                    ':user_id' => $id,
                    ':role' => $role,
                    ':assigned_by' => $assignedBy
                ]);
            }
            
            // Update primary role in users table
            $primaryRole = 'CUSTOMER';
            foreach ($roles as $role) {
                if ($role !== 'CUSTOMER') {
                    $primaryRole = $role;
                    break;
                }
            }
            
            $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :user_id");
            $stmt->execute([':role' => $primaryRole, ':user_id' => $id]);
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Set user roles failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete user
     * 
     * @param string $id User ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('User deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users with optional filters
     * 
     * @param array $filters Optional filters (role, email_verified)
     * @param int $limit Optional limit
     * @param int $offset Optional offset
     * @return array Returns array of users
     */
    public function findAll(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM users WHERE 1=1";
            $params = [];

            if (isset($filters['role'])) {
                $sql .= " AND role = :role";
                $params[':role'] = $filters['role'];
            }

            if (isset($filters['email_verified'])) {
                $sql .= " AND email_verified = :email_verified";
                $params[':email_verified'] = $filters['email_verified'];
            }

            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            // Bind limit and offset as integers
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('User findAll failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if email exists
     * 
     * @param string $email Email to check
     * @param string|null $excludeId Optional user ID to exclude from check
     * @return bool Returns true if email exists, false otherwise
     */
    public function emailExists(string $email, ?string $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
            $params = [':email' => $email];

            if ($excludeId !== null) {
                $sql .= " AND id != :id";
                $params[':id'] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('Email exists check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify password for a user
     * 
     * @param string $email User email
     * @param string $password Plain text password
     * @return array|false Returns user data if password is correct, false otherwise
     */
    public function verifyPassword(string $email, string $password)
    {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password_hash'])) {
            return $user;
        }

        return false;
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
