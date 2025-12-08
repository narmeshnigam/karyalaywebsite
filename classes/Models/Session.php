<?php

namespace Karyalay\Models;

use Karyalay\Database\Connection;
use PDO;
use PDOException;
use DateTime;

/**
 * Session Model Class
 * 
 * Handles CRUD operations for sessions table
 */
class Session
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new session
     * 
     * @param string $userId User ID
     * @param int $expiresInHours Hours until session expires (default 24)
     * @return array|false Returns session data with id and token on success, false on failure
     */
    public function create(string $userId, int $expiresInHours = 24)
    {
        try {
            $id = $this->generateUuid();
            $token = $this->generateToken();
            $expiresAt = (new DateTime())->modify("+{$expiresInHours} hours")->format('Y-m-d H:i:s');

            $sql = "INSERT INTO sessions (id, user_id, token, expires_at) 
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
            error_log('Session creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find session by ID
     * 
     * @param string $id Session ID
     * @return array|false Returns session data or false if not found
     */
    public function findById(string $id)
    {
        try {
            $sql = "SELECT * FROM sessions WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Session find by ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find session by token
     * 
     * @param string $token Session token
     * @return array|false Returns session data or false if not found
     */
    public function findByToken(string $token)
    {
        try {
            $sql = "SELECT * FROM sessions WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':token' => $token]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: false;
        } catch (PDOException $e) {
            error_log('Session find by token failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find all sessions for a user
     * 
     * @param string $userId User ID
     * @return array Returns array of sessions
     */
    public function findByUserId(string $userId): array
    {
        try {
            $sql = "SELECT * FROM sessions WHERE user_id = :user_id ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Session find by user ID failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate session token
     * 
     * @param string $token Session token
     * @return array|false Returns session data if valid and not expired, false otherwise
     */
    public function validate(string $token)
    {
        $session = $this->findByToken($token);
        
        if (!$session) {
            return false;
        }

        // Check if session is expired
        $now = new DateTime();
        $expiresAt = new DateTime($session['expires_at']);
        
        if ($now > $expiresAt) {
            // Delete expired session
            $this->delete($session['id']);
            return false;
        }

        return $session;
    }

    /**
     * Delete session
     * 
     * @param string $id Session ID
     * @return bool Returns true on success, false on failure
     */
    public function delete(string $id): bool
    {
        try {
            $sql = "DELETE FROM sessions WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log('Session deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete session by token
     * 
     * @param string $token Session token
     * @return bool Returns true on success, false on failure
     */
    public function deleteByToken(string $token): bool
    {
        try {
            $sql = "DELETE FROM sessions WHERE token = :token";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':token' => $token]);
        } catch (PDOException $e) {
            error_log('Session deletion by token failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete all sessions for a user
     * 
     * @param string $userId User ID
     * @return bool Returns true on success, false on failure
     */
    public function deleteByUserId(string $userId): bool
    {
        try {
            $sql = "DELETE FROM sessions WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log('Session deletion by user ID failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete expired sessions
     * 
     * @return int Returns number of deleted sessions
     */
    public function deleteExpired(): int
    {
        try {
            $sql = "DELETE FROM sessions WHERE expires_at < NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Expired session deletion failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generate secure session token
     * 
     * @return string Session token
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
