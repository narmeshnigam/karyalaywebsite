<?php
/**
 * Setting Model
 * Represents a system setting
 */

namespace Karyalay\Models;

use PDO;
use Karyalay\Database\Connection;

class Setting
{
    private $db;
    
    public function __construct()
    {
        $this->db = Connection::getInstance();
    }
    
    /**
     * Get a setting value by key
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value
     */
    public function get($key, $default = null)
    {
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = :key");
            $stmt->execute([':key' => $key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['setting_value'] : $default;
        } catch (\PDOException $e) {
            error_log("Error getting setting: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Set a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string $type Setting type (string, integer, boolean, json)
     * @return bool Success status
     */
    public function set($key, $value, $type = 'string')
    {
        try {
            // Check if setting exists
            $stmt = $this->db->prepare("SELECT id FROM settings WHERE setting_key = :key");
            $stmt->execute([':key' => $key]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing setting
                $stmt = $this->db->prepare("
                    UPDATE settings 
                    SET setting_value = :value, setting_type = :type, updated_at = NOW()
                    WHERE setting_key = :key
                ");
                $stmt->execute([
                    ':value' => $value,
                    ':type' => $type,
                    ':key' => $key
                ]);
            } else {
                // Insert new setting
                $stmt = $this->db->prepare("
                    INSERT INTO settings (id, setting_key, setting_value, setting_type)
                    VALUES (UUID(), :key, :value, :type)
                ");
                $stmt->execute([
                    ':key' => $key,
                    ':value' => $value,
                    ':type' => $type
                ]);
            }
            
            return true;
        } catch (\PDOException $e) {
            error_log("Error setting value: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all settings as an associative array
     * 
     * @return array Settings array
     */
    public function getAll()
    {
        try {
            $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            return $settings;
        } catch (\PDOException $e) {
            error_log("Error getting all settings: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get multiple settings by keys
     * 
     * @param array $keys Array of setting keys
     * @return array Associative array of settings
     */
    public function getMultiple($keys)
    {
        try {
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $stmt = $this->db->prepare("
                SELECT setting_key, setting_value 
                FROM settings 
                WHERE setting_key IN ($placeholders)
            ");
            $stmt->execute($keys);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            return $settings;
        } catch (\PDOException $e) {
            error_log("Error getting multiple settings: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete a setting
     * 
     * @param string $key Setting key
     * @return bool Success status
     */
    public function delete($key)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM settings WHERE setting_key = :key");
            $stmt->execute([':key' => $key]);
            return true;
        } catch (\PDOException $e) {
            error_log("Error deleting setting: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set multiple settings at once (batch operation)
     * More efficient than calling set() multiple times
     * 
     * @param array $settings Associative array of key => value pairs
     * @param string $type Setting type for all settings (default: 'string')
     * @return bool Success status
     */
    public function setMultiple(array $settings, $type = 'string')
    {
        if (empty($settings)) {
            return true;
        }
        
        try {
            // Start transaction for atomicity
            $this->db->beginTransaction();
            
            foreach ($settings as $key => $value) {
                // Check if setting exists
                $stmt = $this->db->prepare("SELECT id FROM settings WHERE setting_key = :key");
                $stmt->execute([':key' => $key]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // Update existing setting
                    $stmt = $this->db->prepare("
                        UPDATE settings 
                        SET setting_value = :value, setting_type = :type, updated_at = NOW()
                        WHERE setting_key = :key
                    ");
                    $stmt->execute([
                        ':value' => $value,
                        ':type' => $type,
                        ':key' => $key
                    ]);
                } else {
                    // Insert new setting
                    $stmt = $this->db->prepare("
                        INSERT INTO settings (id, setting_key, setting_value, setting_type)
                        VALUES (UUID(), :key, :value, :type)
                    ");
                    $stmt->execute([
                        ':key' => $key,
                        ':value' => $value,
                        ':type' => $type
                    ]);
                }
            }
            
            // Commit transaction
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            // Rollback on error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error setting multiple values: " . $e->getMessage());
            return false;
        }
    }
}
