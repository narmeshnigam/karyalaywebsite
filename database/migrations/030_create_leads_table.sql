-- Migration: Create leads table
-- Date: 2025-12-08
-- Description: Creates table for capturing lead information from CTA forms

CREATE TABLE IF NOT EXISTS leads (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    company VARCHAR(255) NULL,
    message TEXT NULL,
    source VARCHAR(100) NULL COMMENT 'Page where lead was captured',
    status ENUM('NEW', 'CONTACTED', 'QUALIFIED', 'CONVERTED', 'LOST') NOT NULL DEFAULT 'NEW',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
