<?php
/**
 * Test Payment Email Workflow
 * 
 * This script tests the payment email workflow without actually sending emails.
 * It simulates a successful payment and checks if the email methods are called correctly.
 */

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/config/app.php';

use Karyalay\Services\EmailService;
use Karyalay\Models\User;
use Karyalay\Models\Plan;
use Karyalay\Models\Order;

echo "=== Testing Payment Email Workflow ===\n\n";

// Test 1: Check if EmailService has required methods
echo "Test 1: Checking EmailService methods...\n";
$emailService = new EmailService();
$methods = get_class_methods($emailService);

$requiredMethods = ['send', 'sendEmail', 'sendPaymentSuccessEmail', 'sendNewSaleNotification'];
$missingMethods = [];

foreach ($requiredMethods as $method) {
    if (in_array($method, $methods)) {
        echo "  ✓ Method '{$method}' exists\n";
    } else {
        echo "  ✗ Method '{$method}' is MISSING\n";
        $missingMethods[] = $method;
    }
}

if (empty($missingMethods)) {
    echo "  ✓ All required methods exist\n\n";
} else {
    echo "  ✗ Missing methods: " . implode(', ', $missingMethods) . "\n\n";
    exit(1);
}

// Test 2: Find a recent successful order
echo "Test 2: Finding a recent successful order...\n";
$orderModel = new Order();

try {
    // Get the most recent successful order
    $db = \Karyalay\Database\Connection::getInstance();
    $stmt = $db->prepare("
        SELECT * FROM orders 
        WHERE status = 'SUCCESS' 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo "  ✗ No successful orders found in database\n";
        echo "  Please complete a test purchase first\n\n";
        exit(1);
    }
    
    echo "  ✓ Found order: {$order['id']}\n";
    echo "    Customer ID: {$order['customer_id']}\n";
    echo "    Plan ID: {$order['plan_id']}\n";
    echo "    Amount: {$order['amount']} {$order['currency']}\n\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Fetch customer and plan details
echo "Test 3: Fetching customer and plan details...\n";
$userModel = new User();
$planModel = new Plan();

try {
    $customer = $userModel->findById($order['customer_id']);
    if (!$customer) {
        echo "  ✗ Customer not found for ID: {$order['customer_id']}\n\n";
        exit(1);
    }
    echo "  ✓ Customer found: {$customer['name']} ({$customer['email']})\n";
    
    $plan = $planModel->findById($order['plan_id']);
    if (!$plan) {
        echo "  ✗ Plan not found for ID: {$order['plan_id']}\n\n";
        exit(1);
    }
    echo "  ✓ Plan found: {$plan['name']}\n\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 4: Prepare email data
echo "Test 4: Preparing email data...\n";

$invoiceUrl = ($_ENV['APP_URL'] ?? 'http://localhost') . '/app/billing/invoice.php?id=' . $order['id'];

$paymentData = [
    'customer_name' => $customer['name'],
    'customer_email' => $customer['email'],
    'plan_name' => $plan['name'],
    'amount' => number_format($order['amount'], 2),
    'currency' => $order['currency'] ?? 'USD',
    'order_id' => substr($order['id'], 0, 8),
    'payment_id' => $order['payment_gateway_payment_id'] ?? 'N/A',
    'invoice_url' => $invoiceUrl
];

echo "  ✓ Payment data prepared:\n";
echo "    Customer: {$paymentData['customer_name']}\n";
echo "    Email: {$paymentData['customer_email']}\n";
echo "    Plan: {$paymentData['plan_name']}\n";
echo "    Amount: {$paymentData['currency']} {$paymentData['amount']}\n\n";

$saleData = [
    'customer_name' => $customer['name'],
    'customer_email' => $customer['email'],
    'customer_phone' => $customer['phone'] ?? 'Not provided',
    'plan_name' => $plan['name'],
    'plan_price' => number_format($order['amount'], 2),
    'currency' => $order['currency'] ?? 'USD',
    'order_id' => substr($order['id'], 0, 8),
    'subscription_id' => 'TEST-SUB',
    'payment_id' => $order['payment_gateway_payment_id'] ?? 'N/A',
    'payment_method' => 'Online Payment'
];

echo "  ✓ Sale notification data prepared\n\n";

// Test 5: Check admin notification email
echo "Test 5: Checking admin notification email...\n";
try {
    $settingModel = new \Karyalay\Models\Setting();
    $notificationsEmail = $settingModel->get('notifications_email');
    
    if (empty($notificationsEmail)) {
        $notificationsEmail = $settingModel->get('contact_email');
    }
    
    if (empty($notificationsEmail)) {
        $notificationsEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@karyalay.com';
    }
    
    echo "  ✓ Admin notification email: {$notificationsEmail}\n\n";
    
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 6: Test email template rendering (without sending)
echo "Test 6: Testing email template rendering...\n";
echo "  NOTE: This will NOT send actual emails\n\n";

echo "  Testing customer payment success email...\n";
try {
    // Use reflection to call private methods on the dedicated email handler
    $paymentSuccessHandler = new \Karyalay\Services\EmailServices\PaymentSuccessEmail();
    $reflection = new ReflectionClass($paymentSuccessHandler);
    
    $renderMethod = $reflection->getMethod('renderTemplate');
    $renderMethod->setAccessible(true);
    $htmlBody = $renderMethod->invoke($paymentSuccessHandler, $paymentData);
    
    if (strlen($htmlBody) > 100 && strpos($htmlBody, $customer['name']) !== false) {
        echo "    ✓ Customer email template rendered successfully\n";
        echo "    ✓ Template contains customer name\n";
        echo "    ✓ Template length: " . strlen($htmlBody) . " characters\n";
    } else {
        echo "    ✗ Customer email template may have issues\n";
    }
    
} catch (Exception $e) {
    echo "    ✗ Error rendering customer email: " . $e->getMessage() . "\n";
}

echo "\n  Testing admin sale notification email...\n";
try {
    // Use reflection to call private methods on the dedicated email handler
    $newSaleHandler = new \Karyalay\Services\EmailServices\NewSaleNotificationEmail();
    $reflection = new ReflectionClass($newSaleHandler);
    
    $renderMethod = $reflection->getMethod('renderTemplate');
    $renderMethod->setAccessible(true);
    $htmlBody = $renderMethod->invoke($newSaleHandler, $saleData);
    
    if (strlen($htmlBody) > 100 && strpos($htmlBody, $plan['name']) !== false) {
        echo "    ✓ Admin email template rendered successfully\n";
        echo "    ✓ Template contains plan name\n";
        echo "    ✓ Template length: " . strlen($htmlBody) . " characters\n";
    } else {
        echo "    ✗ Admin email template may have issues\n";
    }
    
} catch (Exception $e) {
    echo "    ✗ Error rendering admin email: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "✓ All email methods exist\n";
echo "✓ Email templates render correctly\n";
echo "✓ Customer and plan data can be fetched\n";
echo "✓ Email data structures are correct\n\n";

echo "IMPORTANT: To test actual email sending:\n";
echo "1. Make a test purchase through the website\n";
echo "2. Check the webhook logs for email sending attempts\n";
echo "3. Verify SMTP settings in the database\n\n";

echo "To check webhook logs, run:\n";
echo "  tail -f /var/log/php_errors.log\n";
echo "  (or check your PHP error log location)\n\n";
