-- Migration: Create testimonials table
-- Date: 2025-12-08
-- Description: Creates table for customer testimonials with ratings

CREATE TABLE IF NOT EXISTS testimonials (
    id CHAR(36) PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    customer_title VARCHAR(255) NULL,
    customer_company VARCHAR(255) NULL,
    customer_image VARCHAR(500) NULL,
    testimonial_text TEXT NOT NULL,
    rating TINYINT NOT NULL DEFAULT 5,
    display_order INT NOT NULL DEFAULT 0,
    is_featured BOOLEAN NOT NULL DEFAULT FALSE,
    status ENUM('DRAFT', 'PUBLISHED', 'ARCHIVED') NOT NULL DEFAULT 'DRAFT',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_featured (is_featured),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
