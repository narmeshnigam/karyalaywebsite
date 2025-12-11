<?php
/**
 * Test FAQ Creation
 */

require_once __DIR__ . '/../config/bootstrap.php';

use Karyalay\Models\Faq;
use Karyalay\Database\Connection;

echo "Testing FAQ system...\n\n";

try {
    $db = Connection::getInstance();
    
    // Check if tables exist
    echo "1. Checking if tables exist...\n";
    $tables = $db->query("SHOW TABLES LIKE 'faqs'")->fetchAll();
    if (empty($tables)) {
        die("✗ FAQs table does not exist. Run migration first.\n");
    }
    echo "✓ FAQs table exists\n\n";
    
    $tables = $db->query("SHOW TABLES LIKE 'faq_categories'")->fetchAll();
    if (empty($tables)) {
        die("✗ FAQ categories table does not exist. Run migration first.\n");
    }
    echo "✓ FAQ categories table exists\n\n";
    
    // Check table structure
    echo "2. Checking table structure...\n";
    $columns = $db->query("DESCRIBE faqs")->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(', ', $columns) . "\n\n";
    
    // Try to create a test FAQ
    echo "3. Creating test FAQ...\n";
    $faqModel = new Faq();
    
    $testData = [
        'question' => 'Test Question?',
        'answer' => 'This is a test answer.',
        'category' => 'General Questions',
        'display_order' => 0,
        'status' => 'PUBLISHED'
    ];
    
    $result = $faqModel->create($testData);
    
    if ($result) {
        echo "✓ Test FAQ created successfully!\n";
        echo "FAQ ID: " . $result['id'] . "\n";
        echo "Question: " . $result['question'] . "\n\n";
        
        // Clean up - delete test FAQ
        echo "4. Cleaning up test FAQ...\n";
        $faqModel->delete($result['id']);
        echo "✓ Test FAQ deleted\n\n";
        
        echo "=== All tests passed! ===\n";
    } else {
        echo "✗ Failed to create test FAQ\n";
        echo "Check error logs for details\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
