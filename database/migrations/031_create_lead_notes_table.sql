-- Migration: Create lead_notes table
-- Date: 2025-12-10
-- Description: Creates table for storing notes on leads

CREATE TABLE IF NOT EXISTS lead_notes (
    id CHAR(36) PRIMARY KEY,
    lead_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_lead_id (lead_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add notes_count column to leads table for quick reference
ALTER TABLE leads ADD COLUMN notes_count INT DEFAULT 0 AFTER status;
