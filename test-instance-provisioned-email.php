<?php
/**
 * Test Instance Provisioned Email
 * 
 * This script tests the instance provisioned email by sending it
 * for a recent successful order with port allocation.
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "=== Testing Instance Provisioned Email ===\n\n";

// Get the most recent order with port allocation
$db = \Karyalay\Database\Connection::getInstance();

echo "Step 1: Finding recent order with port allocation...\n";
$stmt = $db->prepare("
    SELECT o.*, s.id as subscription_id, p.instance_url
    FROM orders o
    LEFT JOIN subscriptions s ON s.order_id = o.id
    LEFT JOIN ports p ON p.assigned_subscription_id = s.id
    WHERE o.status = 'SUCCESS' 
    AND p.instance_url IS NOT NULL
    ORDER BY o.created_at DESC 
    LIMIT 1
");
$stmt->execute();
$orderData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orderData) {
    echo "  ✗ No orders found with port allocation\n";
    echo "  Please complete a test purchase first\n\n";
    exit(1);
}

echo "  ✓ Found order: " . substr($orderData['id'], 0, 8) . "...\n";
echo "  ✓ Instance URL: {$orderData['instance_url']}\n\n";

// Fetch customer and plan details
echo "Step 2: Fetching customer and plan details...\n";
$userModel = new \Karyalay\Models\User();
$planModel = new \Karyalay\Models\Plan();

$customer = $userModel->findById($orderData['customer_id']);
$plan = $planModel->findById($orderData['plan_id']);

if (!$customer || !$plan) {
    echo "  ✗ Customer or plan not found\n";
    exit(1);
}

echo "  ✓ Customer: {$customer['name']} ({$customer['email']})\n";
echo "  ✓ Plan: {$plan['name']}\n\n";

// Prepare instance data
echo "Step 3: Preparing instance data...\n";
$myPortUrl = ($_ENV['APP_URL'] ?? 'http://localhost') . '/app/my-port.php';

$instanceData = [
    'customer_name' => $customer['name'],
    'customer_email' => $customer['email'],
    'plan_name' => $plan['name'],
    'instance_url' => $orderData['instance_url'],
    'my_port_url' => $myPortUrl
];

echo "  ✓ Instance data prepared:\n";
echo "    Customer: {$instanceData['customer_name']}\n";
echo "    Email: {$instanceData['customer_email']}\n";
echo "    Plan: {$instanceData['plan_name']}\n";
echo "    Instance URL: {$instanceData['instance_url']}\n";
echo "    My Port URL: {$instanceData['my_port_url']}\n\n";

// Confirm before sending
echo "=== Ready to Send Email ===\n";
echo "To: {$customer['email']}\n";
echo "Subject: Your Instance is Ready - Karyalay\n\n";
echo "⚠ WARNING: This will send a REAL email!\n";
echo "Continue? (y/n): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) !== 'y') {
    echo "Aborted\n";
    exit(0);
}
fclose($handle);

echo "\n";

// Send email
echo "Step 4: Sending instance provisioned email...\n";
$emailService = new \Karyalay\Services\EmailService();

try {
    $result = $emailService->sendInstanceProvisionedEmail($instanceData);
    
    if ($result) {
        echo "  ✓ Instance provisioned email sent successfully!\n\n";
        echo "=== Success ===\n";
        echo "✓ Email sent to: {$customer['email']}\n\n";
        echo "Please check:\n";
        echo "1. Customer inbox: {$customer['email']}\n";
        echo "2. Spam/Junk folder if not in inbox\n";
        echo "3. Email should contain:\n";
        echo "   - Instance URL: {$instanceData['instance_url']}\n";
        echo "   - Link to My Port page\n";
        echo "   - Setup instructions\n";
    } else {
        echo "  ✗ Failed to send email\n\n";
        echo "=== Failure ===\n";
        echo "Check PHP error logs for details:\n";
        echo "  tail -f /Applications/XAMPP/xamppfiles/logs/php_error_log\n";
    }
} catch (Exception $e) {
    echo "  ✗ Exception: " . $e->getMessage() . "\n\n";
    echo "=== Error ===\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";
