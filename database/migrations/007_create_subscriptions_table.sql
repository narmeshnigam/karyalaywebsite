-- Create subscriptions table
CREATE TABLE subscriptions (
    id CHAR(36) PRIMARY KEY,
    customer_id CHAR(36) NOT NULL,
    plan_id CHAR(36) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('ACTIVE', 'EXPIRED', 'CANCELLED', 'PENDING_ALLOCATION') NOT NULL DEFAULT 'ACTIVE',
    assigned_port_id CHAR(36),
    order_id CHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer_id (customer_id),
    INDEX idx_status (status),
    INDEX idx_end_date (end_date),
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_port_id) REFERENCES ports(id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
