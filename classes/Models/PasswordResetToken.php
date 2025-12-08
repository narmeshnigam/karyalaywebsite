<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;
use DateTime;

/**
 * PasswordResetToken Model Class
 * 
 * Handles CRUD operations for password_reset_tokens table
 */
class PasswordResetToken
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new password reset token
     * 
     * @param string $userId User ID
     * @param int $expiresInHours Hours until token expires (default 1)
     * @return array|false Returns token data with id and token on success, false on failure
     */
    public function create(string $userId, int $expiresInHours = 1)
    {
        try {
            // Delete any existing tokens for this user
            $this->deleteByUserId($userId);

            $id = $this->generateUuid();
            $token = $this->generateToken();
            $expiresAt = (new DateTime())->modify("+{$expiresInHours} hours")->format('Y-m-d H:i:s');

            $sql = "INSERT INTO password_reset_tokens (id, user_id, token, expires_at) 
                    VALUES (:id, :user_id, :token, :expires_at)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId,
                ':token' => $token,
                ':expires_at' => $expiresAt
            ]);

            return $this->findById($id);
        } catch (PDOException $e) {
            error_log('Password reset token creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find token by ID
     * 
     * @param string $id Token ID
     * @return array|false Returns token data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM password_reset_tokens WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Password reset token find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find token by token string
     * 
     * @param string $token Token string
     * @return array|false Returns token data or false if not found
     */
    public function findByToken(string $token)
    {
        try {
            $sql = "SELECT * FROM password_reset_tokens WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Password reset token find by token failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find token by user ID
     * 
     * @param string $userId User ID
     * @return array|false Returns token data or false if not found
     */
    public function findByUserId(string $userId)
    {
        try {
            $sql = "SELECT * FROM password_reset_tokens WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Password reset token find by user ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate password reset token
     * 
     * @param string $token Token string
     * @return array|false Returns token data if valid and not expired, false otherwise
     */
    public function validate(string $token)
    {
        $resetToken = $this->findByToken($token);
        
        if (!$resetToken) {
            return false;
        }

        // Check if token is expired
        $now = new DateTime();
        $expiresAt = new DateTime($resetToken['expires_at']);
        
        if ($now > $expiresAt) {
            // Delete expired token
            $this->delete($resetToken['id']);
            return false;
        }

        return $resetToken;
    }

    /**
     * Delete token
     * 
     * @param string $id Token ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM password_reset_tokens WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Password reset token deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete token by token string
     * 
     * @param string $token Token string
     * @return bool Returns true on success, false on failure
     */
    public function deleteByToken(string $token): bool
    {
        try {
            $sql = "DELETE FROM password_reset_tokens WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':token' => $token]);
        } catch (PDOException $e) {
            error_log('Password reset token deletion by token failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete all tokens for a user
     * 
     * @param string $userId User ID
     * @return bool Returns true on success, false on failure
     */
    public function deleteByUserId(string $userId): bool
    {
        try {
            $sql = "DELETE FROM password_reset_tokens WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log('Password reset token deletion by user ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete expired tokens
     * 
     * @return int Returns number of deleted tokens
     */
    public function deleteExpired(): int
    {
        try {
            $sql = "DELETE FROM password_reset_tokens WHERE expires_at < NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Expired password reset token deletion failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generate secure token
     * 
     * @return string Token
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
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
