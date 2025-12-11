-- Migration 050: Create solution_features linking table
-- This creates a proper many-to-many relationship between solutions and features

-- Create the linking table
CREATE TABLE IF NOT EXISTS solution_features (
    id CHAR(36) PRIMARY KEY,
    solution_id CHAR(36) NOT NULL,
    feature_id CHAR(36) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_highlighted BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    CONSTRAINT fk_solution_features_solution FOREIGN KEY (solution_id) 
        REFERENCES solutions(id) ON DELETE CASCADE,
    CONSTRAINT fk_solution_features_feature FOREIGN KEY (feature_id) 
        REFERENCES features(id) ON DELETE CASCADE,
    
    -- Unique constraint to prevent duplicate links
    UNIQUE KEY unique_solution_feature (solution_id, feature_id),
    
    -- Indexes for performance
    INDEX idx_solution_features_solution (solution_id),
    INDEX idx_solution_features_feature (feature_id),
    INDEX idx_solution_features_order (solution_id, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add new columns to solutions table for enhanced detail page
ALTER TABLE solutions 
    ADD COLUMN tagline VARCHAR(255) AFTER description,
    ADD COLUMN hero_image VARCHAR(500) AFTER icon_image,
    ADD COLUMN video_url VARCHAR(500) AFTER hero_image,
    ADD COLUMN use_cases JSON AFTER benefits,
    ADD COLUMN stats JSON AFTER use_cases,
    ADD COLUMN color_theme VARCHAR(7) DEFAULT '#667eea' AFTER stats;

-- Add new columns to features table for better display
ALTER TABLE features 
    ADD COLUMN tagline VARCHAR(255) AFTER description,
    ADD COLUMN category VARCHAR(100) AFTER tagline,
    ADD COLUMN is_core BOOLEAN NOT NULL DEFAULT FALSE AFTER category,
    ADD COLUMN color_accent VARCHAR(7) DEFAULT '#667eea' AFTER is_core;

-- Add index for feature category
CREATE INDEX idx_features_category ON features(category);
CREATE INDEX idx_features_is_core ON features(is_core);
