-- Create FAQs table for dynamic FAQ management
CREATE TABLE IF NOT EXISTS faqs (
    id CHAR(36) PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'General',
    display_order INT DEFAULT 0,
    status ENUM('PUBLISHED', 'DRAFT') DEFAULT 'DRAFT',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_faqs_category (category),
    INDEX idx_faqs_status (status),
    INDEX idx_faqs_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create FAQ categories table for custom categories
CREATE TABLE IF NOT EXISTS faq_categories (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    display_order INT DEFAULT 0,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_faq_categories_slug (slug),
    INDEX idx_faq_categories_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO faq_categories (id, name, slug, description, display_order, status) VALUES
(UUID(), 'General Questions', 'general', 'General questions about our platform', 1, 'ACTIVE'),
(UUID(), 'Pricing & Plans', 'pricing', 'Questions about pricing and subscription plans', 2, 'ACTIVE'),
(UUID(), 'Features & Functionality', 'features', 'Questions about features and how things work', 3, 'ACTIVE'),
(UUID(), 'Support & Training', 'support', 'Questions about support and training resources', 4, 'ACTIVE');
