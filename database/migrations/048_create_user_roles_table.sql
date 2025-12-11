-- Migration: Create user_roles table for multiple roles per user
-- This allows users to have multiple roles assigned

-- Create user_roles junction table
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    role VARCHAR(50) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by CHAR(36) NULL,
    UNIQUE KEY unique_user_role (user_id, role),
    INDEX idx_user_id (user_id),
    INDEX idx_role (role),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update users table role ENUM to include new roles
ALTER TABLE users MODIFY COLUMN role ENUM(
    'CUSTOMER',
    'ADMIN',
    'SUPPORT',
    'INFRASTRUCTURE',
    'SALES',
    'SALES_MANAGER',
    'OPERATIONS',
    'CONTENT_MANAGER'
) NOT NULL DEFAULT 'CUSTOMER';

-- Migrate existing users to user_roles table
-- All existing users get their current role added to user_roles
INSERT INTO user_roles (user_id, role)
SELECT id, role FROM users
ON DUPLICATE KEY UPDATE role = VALUES(role);

-- Ensure all users have CUSTOMER role (default role for everyone)
INSERT INTO user_roles (user_id, role)
SELECT id, 'CUSTOMER' FROM users WHERE role != 'CUSTOMER'
ON DUPLICATE KEY UPDATE role = VALUES(role);
