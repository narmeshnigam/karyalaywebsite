-- Create ticket_messages table
CREATE TABLE ticket_messages (
    id CHAR(36) PRIMARY KEY,
    ticket_id CHAR(36) NOT NULL,
    author_id CHAR(36) NOT NULL,
    author_type ENUM('CUSTOMER', 'ADMIN') NOT NULL,
    content TEXT NOT NULL,
    is_internal BOOLEAN NOT NULL DEFAULT FALSE,
    attachments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
