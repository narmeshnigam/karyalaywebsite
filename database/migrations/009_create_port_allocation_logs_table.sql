-- Create port_allocation_logs table
CREATE TABLE port_allocation_logs (
    id CHAR(36) PRIMARY KEY,
    port_id CHAR(36) NOT NULL,
    subscription_id CHAR(36) NOT NULL,
    customer_id CHAR(36) NOT NULL,
    action ENUM('ASSIGNED', 'REASSIGNED', 'RELEASED') NOT NULL,
    performed_by CHAR(36),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_port_id (port_id),
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_customer_id (customer_id),
    INDEX idx_timestamp (timestamp),
    FOREIGN KEY (port_id) REFERENCES ports(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
