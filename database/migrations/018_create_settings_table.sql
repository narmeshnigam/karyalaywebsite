-- Create settings table for storing site configuration
CREATE TABLE IF NOT EXISTS settings (
    id CHAR(36) PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO settings (id, setting_key, setting_value, setting_type) VALUES
(UUID(), 'site_name', 'SellerPortal', 'string'),
(UUID(), 'contact_email', 'contact@karyalay.com', 'string'),
(UUID(), 'contact_phone', '+1 (555) 123-4567', 'string'),
(UUID(), 'footer_text', '© 2024 Karyalay. All rights reserved.', 'string'),
(UUID(), 'logo_url', '', 'string'),
(UUID(), 'favicon_url', '', 'string'),
(UUID(), 'primary_color', '#3b82f6', 'string'),
(UUID(), 'secondary_color', '#10b981', 'string'),
(UUID(), 'meta_title', 'Karyalay - Business Management Platform', 'string'),
(UUID(), 'meta_description', 'Comprehensive business management platform with subscription-based services', 'string'),
(UUID(), 'og_image_url', '', 'string');
