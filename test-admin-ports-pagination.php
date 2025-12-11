<?php
/**
 * Test Admin Ports Pagination Fix
 */

// Simulate request
$_GET['page'] = 1;
$_SERVER['REQUEST_URI'] = '/admin/ports.php?page=1';
$_SERVER['HTTP_HOST'] = 'localhost';

require_once __DIR__ . '/includes/template_helpers.php';

echo "=== Testing Admin Ports Pagination Fix ===\n\n";

// Test 1: Old signature (as used in admin pages)
echo "Test 1: Old signature - render_pagination(1, 2, '/admin/ports.php')\n";
try {
    ob_start();
    render_pagination(1, 2, '/admin/ports.php');
    $output = ob_get_clean();
    
    if (strlen($output) > 0 && strpos($output, 'pagination') !== false) {
        echo "  ✓ Pagination rendered successfully\n";
        echo "  Output length: " . strlen($output) . " characters\n";
    } else {
        echo "  ✗ Pagination failed to render\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: New signature
echo "Test 2: New signature - render_pagination(40, 20, [])\n";
try {
    ob_start();
    render_pagination(40, 20, []);
    $output = ob_get_clean();
    
    if (strlen($output) > 0 && strpos($output, 'pagination') !== false) {
        echo "  ✓ Pagination rendered successfully\n";
        echo "  Output length: " . strlen($output) . " characters\n";
    } else {
        echo "  ✗ Pagination failed to render\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Simulate actual admin ports scenario
echo "Test 3: Simulating admin ports page with 22 ports\n";
try {
    $total_ports = 22;
    $per_page = 20;
    $total_pages = ceil($total_ports / $per_page);
    $page = 1;
    $base_url = '/admin/ports.php';
    
    ob_start();
    render_pagination($page, $total_pages, $base_url);
    $output = ob_get_clean();
    
    if (strlen($output) > 0) {
        echo "  ✓ Pagination rendered for 22 ports\n";
        echo "  Total pages: $total_pages\n";
        echo "  Current page: $page\n";
        
        // Check for expected elements
        $hasNext = strpos($output, 'Next') !== false;
        $hasPrevious = strpos($output, 'Previous') !== false;
        
        echo "  Has 'Next' button: " . ($hasNext ? 'Yes' : 'No') . "\n";
        echo "  Has 'Previous' button: " . ($hasPrevious ? 'Yes' : 'No') . "\n";
    } else {
        echo "  ✗ Pagination failed to render\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== All Tests Completed ===\n";
echo "\nThe pagination function now supports both old and new signatures.\n";
echo "Admin pages can continue using: render_pagination(\$page, \$total_pages, \$base_url)\n";
echo "New code can use: render_pagination(\$totalItems, \$perPage, \$options)\n";
