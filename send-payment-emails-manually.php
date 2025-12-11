<?php
/**
 * Manually Send Payment Emails for Existing Order
 * 
 * This script manually sends payment confirmation emails
 * for an existing successful order. Use this to test the
 * email sending without making a new purchase.
 * 
 * Usage: php send-payment-emails-manually.php [order_id]
 */

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/config/app.php';

use Karyalay\Services\EmailService;
use Karyalay\Models\User;
use Karyalay\Models\Plan;
use Karyalay\Models\Order;
use Karyalay\Models\Setting;
use Karyalay\Models\Subscription;

// Get order ID from command line or use most recent
$orderId = $argv[1] ?? null;

echo "=== Manual Payment Email Sender ===\n\n";

// Get order
$db = \Karyalay\Database\Connection::getInstance();

if ($orderId) {
    echo "Looking for order: {$orderId}\n";
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id");
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    echo "No order ID provided, using most recent successful order\n";
    $stmt = $db->prepare("
        SELECT * FROM orders 
        WHERE status = 'SUCCESS' 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$order) {
    echo "✗ Order not found\n";
    exit(1);
}

echo "✓ Found order: " . substr($order['id'], 0, 8) . "...\n";
echo "  Status: {$order['status']}\n";
echo "  Amount: {$order['amount']} {$order['currency']}\n";
echo "  Created: {$order['created_at']}\n\n";

if ($order['status'] !== 'SUCCESS') {
    echo "⚠ Warning: Order status is not SUCCESS\n";
    echo "Continue anyway? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) !== 'y') {
        echo "Aborted\n";
        exit(0);
    }
    fclose($handle);
}

// Fetch customer and plan
echo "Fetching customer and plan details...\n";
$userModel = new User();
$planModel = new Plan();

$customer = $userModel->findById($order['customer_id']);
$plan = $planModel->findById($order['plan_id']);

if (!$customer) {
    echo "✗ Customer not found for ID: {$order['customer_id']}\n";
    exit(1);
}

if (!$plan) {
    echo "✗ Plan not found for ID: {$order['plan_id']}\n";
    exit(1);
}

echo "✓ Customer: {$customer['name']} ({$customer['email']})\n";
echo "✓ Plan: {$plan['name']}\n\n";

// Get subscription ID if exists
$stmt = $db->prepare("SELECT id FROM subscriptions WHERE order_id = :order_id LIMIT 1");
$stmt->execute([':order_id' => $order['id']]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);
$subscriptionId = $subscription ? substr($subscription['id'], 0, 8) : 'N/A';

// Prepare email data
echo "Preparing email data...\n";
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

$saleData = [
    'customer_name' => $customer['name'],
    'customer_email' => $customer['email'],
    'customer_phone' => $customer['phone'] ?? 'Not provided',
    'plan_name' => $plan['name'],
    'plan_price' => number_format($order['amount'], 2),
    'currency' => $order['currency'] ?? 'USD',
    'order_id' => substr($order['id'], 0, 8),
    'subscription_id' => $subscriptionId,
    'payment_id' => $order['payment_gateway_payment_id'] ?? 'N/A',
    'payment_method' => 'Online Payment'
];

echo "✓ Email data prepared\n\n";

// Get admin email
$settingModel = new Setting();
$notificationsEmail = $settingModel->get('notifications_email');

if (empty($notificationsEmail)) {
    $notificationsEmail = $settingModel->get('contact_email');
}

if (empty($notificationsEmail)) {
    $notificationsEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@karyalay.com';
}

echo "Admin notification email: {$notificationsEmail}\n\n";

// Confirm before sending
echo "=== Ready to Send Emails ===\n";
echo "Customer email: {$customer['email']}\n";
echo "Admin email: {$notificationsEmail}\n\n";
echo "⚠ WARNING: This will send REAL emails!\n";
echo "Continue? (y/n): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) !== 'y') {
    echo "Aborted\n";
    exit(0);
}
fclose($handle);

echo "\n";

// Send emails
echo "Sending emails...\n\n";
$emailService = new EmailService();

// Send customer email
echo "1. Sending payment success email to customer...\n";
echo "   To: {$customer['email']}\n";
$customerResult = $emailService->sendPaymentSuccessEmail($paymentData);

if ($customerResult) {
    echo "   ✓ Customer email sent successfully!\n\n";
} else {
    echo "   ✗ Failed to send customer email\n\n";
}

// Send admin email
echo "2. Sending new sale notification to admin...\n";
echo "   To: {$notificationsEmail}\n";
$adminResult = $emailService->sendNewSaleNotification($saleData);

if ($adminResult) {
    echo "   ✓ Admin email sent successfully!\n\n";
} else {
    echo "   ✗ Failed to send admin email\n\n";
}

// Summary
echo "=== Summary ===\n";
if ($customerResult && $adminResult) {
    echo "✓ Both emails sent successfully!\n";
    echo "\nPlease check:\n";
    echo "1. Customer inbox: {$customer['email']}\n";
    echo "2. Admin inbox: {$notificationsEmail}\n";
    echo "3. Spam folders if emails not in inbox\n";
} else {
    echo "✗ Some emails failed to send\n";
    echo "\nCheck:\n";
    echo "1. SMTP settings in database\n";
    echo "2. PHP error logs for details\n";
    echo "3. SMTP provider's sending limits\n";
}

echo "\n";
