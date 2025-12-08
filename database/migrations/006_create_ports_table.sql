-- Create ports table
CREATE TABLE ports (
    id CHAR(36) PRIMARY KEY,
    instance_url VARCHAR(255) NOT NULL,
    port_number INT,
    plan_id CHAR(36) NOT NULL,
    status ENUM('AVAILABLE', 'RESERVED', 'ASSIGNED', 'DISABLED') NOT NULL DEFAULT 'AVAILABLE',
    assigned_customer_id CHAR(36),
    assigned_subscription_id CHAR(36),
    assigned_at TIMESTAMP NULL,
    server_region VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_instance (instance_url, port_number),
    INDEX idx_status (status),
    INDEX idx_plan_id (plan_id),
    INDEX idx_assigned_subscription_id (assigned_subscription_id),
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_customer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
