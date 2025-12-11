<?php
/**
 * Create FAQ Tables
 */

require_once __DIR__ . '/../config/bootstrap.php';
use Karyalay\Database\Connection;

$db = Connection::getInstance();

echo "Creating FAQ tables...\n\n";

// Create faqs table
$sql1 = "CREATE TABLE IF NOT EXISTS faqs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $db->exec($sql1);
    echo "✓ Created faqs table\n";
} catch (PDOException $e) {
    echo "✗ Error creating faqs table: " . $e->getMessage() . "\n";
}

// Create faq_categories table
$sql2 = "CREATE TABLE IF NOT EXISTS faq_categories (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $db->exec($sql2);
    echo "✓ Created faq_categories table\n";
} catch (PDOException $e) {
    echo "✗ Error creating faq_categories table: " . $e->getMessage() . "\n";
}

// Insert default categories
$categories = [
    ['General Questions', 'general', 'General questions about our platform', 1],
    ['Pricing & Plans', 'pricing', 'Questions about pricing and subscription plans', 2],
    ['Features & Functionality', 'features', 'Questions about features and how things work', 3],
    ['Support & Training', 'support', 'Questions about support and training resources', 4]
];

echo "\nInserting default categories...\n";

foreach ($categories as $cat) {
    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $sql = "INSERT IGNORE INTO faq_categories (id, name, slug, description, display_order, status) 
            VALUES (?, ?, ?, ?, ?, 'ACTIVE')";
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$id, $cat[0], $cat[1], $cat[2], $cat[3]]);
        echo "✓ Added category: {$cat[0]}\n";
    } catch (PDOException $e) {
        echo "  (Category may already exist)\n";
    }
}

echo "\n=== Setup complete! ===\n";
