<?php
/**
 * Test Orders Payment Columns Update
 * 
 * This script tests the new pg_order_id and pg_payment_id columns
 */

require_once __DIR__ . '/vendor/autoload.php';

use Karyalay\Models\Order;
use Karyalay\Services\OrderService;

echo "=== Testing Orders Payment Columns Update ===\n\n";

$orderModel = new Order();
$orderService = new OrderService();

// Test 1: Check if columns exist
echo "Test 1: Checking if new columns exist...\n";
try {
    $db = Karyalay\Database\Connection::getInstance();
    $stmt = $db->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hasPgOrderId = in_array('pg_order_id', $columns);
    $hasPgPaymentId = in_array('pg_payment_id', $columns);
    
    if ($hasPgOrderId && $hasPgPaymentId) {
        echo "  ✓ Both columns exist\n";
    } else {
        echo "  ✗ Missing columns:\n";
        if (!$hasPgOrderId) echo "    - pg_order_id\n";
        if (!$hasPgPaymentId) echo "    - pg_payment_id\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check if index exists
echo "\nTest 2: Checking if index exists...\n";
try {
    $stmt = $db->query("SHOW INDEX FROM orders WHERE Key_name = 'idx_pg_payment_id'");
    $index = $stmt->fetch();
    
    if ($index) {
        echo "  ✓ Index idx_pg_payment_id exists\n";
    } else {
        echo "  ✗ Index idx_pg_payment_id not found\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Test Order model methods
echo "\nTest 3: Testing Order model methods...\n";
try {
    // Get a sample order
    $stmt = $db->query("SELECT * FROM orders WHERE pg_order_id IS NOT NULL LIMIT 1");
    $sampleOrder = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sampleOrder) {
        // Test findByPgOrderId
        $order = $orderModel->findByPgOrderId($sampleOrder['pg_order_id']);
        if ($order && $order['id'] === $sampleOrder['id']) {
            echo "  ✓ findByPgOrderId() works\n";
        } else {
            echo "  ✗ findByPgOrderId() failed\n";
        }
        
        // Test legacy method
        $order2 = $orderModel->findByPaymentGatewayId($sampleOrder['pg_order_id']);
        if ($order2 && $order2['id'] === $sampleOrder['id']) {
            echo "  ✓ Legacy findByPaymentGatewayId() works\n";
        } else {
            echo "  ✗ Legacy findByPaymentGatewayId() failed\n";
        }
        
        // Test findByPgPaymentId if payment ID exists
        if (!empty($sampleOrder['pg_payment_id'])) {
            $order3 = $orderModel->findByPgPaymentId($sampleOrder['pg_payment_id']);
            if ($order3 && $order3['id'] === $sampleOrder['id']) {
                echo "  ✓ findByPgPaymentId() works\n";
            } else {
                echo "  ✗ findByPgPaymentId() failed\n";
            }
        } else {
            echo "  ℹ No pg_payment_id to test with\n";
        }
    } else {
        echo "  ℹ No orders with pg_order_id to test with\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 4: Test OrderService methods
echo "\nTest 4: Testing OrderService methods...\n";
try {
    if ($sampleOrder) {
        // Test getOrderByPgOrderId
        $order = $orderService->getOrderByPgOrderId($sampleOrder['pg_order_id']);
        if ($order && $order['id'] === $sampleOrder['id']) {
            echo "  ✓ getOrderByPgOrderId() works\n";
        } else {
            echo "  ✗ getOrderByPgOrderId() failed\n";
        }
        
        // Test legacy method
        $order2 = $orderService->getOrderByPaymentGatewayId($sampleOrder['pg_order_id']);
        if ($order2 && $order2['id'] === $sampleOrder['id']) {
            echo "  ✓ Legacy getOrderByPaymentGatewayId() works\n";
        } else {
            echo "  ✗ Legacy getOrderByPaymentGatewayId() failed\n";
        }
        
        // Test getOrderByPgPaymentId if payment ID exists
        if (!empty($sampleOrder['pg_payment_id'])) {
            $order3 = $orderService->getOrderByPgPaymentId($sampleOrder['pg_payment_id']);
            if ($order3 && $order3['id'] === $sampleOrder['id']) {
                echo "  ✓ getOrderByPgPaymentId() works\n";
            } else {
                echo "  ✗ getOrderByPgPaymentId() failed\n";
            }
        }
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 5: Display sample data
echo "\nTest 5: Sample order data...\n";
try {
    $stmt = $db->query("SELECT id, pg_order_id, pg_payment_id, status FROM orders LIMIT 5");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orders)) {
        echo "  ℹ No orders in database\n";
    } else {
        echo "  Sample orders:\n";
        foreach ($orders as $order) {
            printf("    ID: %s | PG Order: %s | PG Payment: %s | Status: %s\n",
                substr($order['id'], 0, 8),
                $order['pg_order_id'] ? substr($order['pg_order_id'], 0, 20) : 'NULL',
                $order['pg_payment_id'] ? substr($order['pg_payment_id'], 0, 20) : 'NULL',
                $order['status']
            );
        }
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== All Tests Completed ===\n";
echo "\nNext Steps:\n";
echo "1. Test creating a new order through the checkout flow\n";
echo "2. Complete a payment and verify pg_payment_id is stored\n";
echo "3. Check admin orders list displays both IDs correctly\n";
echo "4. Check customer billing history shows payment ID\n";
echo "5. Verify invoice shows payment ID\n";
