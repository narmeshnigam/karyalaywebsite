-- Create leads table
CREATE TABLE leads (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    message TEXT,
    source ENUM('CONTACT_FORM', 'DEMO_REQUEST') NOT NULL,
    status ENUM('NEW', 'CONTACTED', 'QUALIFIED', 'CONVERTED', 'CLOSED') NOT NULL DEFAULT 'NEW',
    contacted_at TIMESTAMP NULL,
    notes TEXT,
    company_name VARCHAR(255),
    preferred_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
