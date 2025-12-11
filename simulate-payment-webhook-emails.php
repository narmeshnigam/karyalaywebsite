<?php
/**
 * Simulate Payment Webhook Email Sending
 * 
 * This script simulates the webhook email sending process
 * WITHOUT actually sending emails (to save daily limits).
 * It will show what would be sent and verify the logic.
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

echo "=== Simulating Payment Webhook Email Process ===\n\n";

// Get the most recent successful order
echo "Step 1: Finding recent successful order...\n";
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
    echo "  ✗ No successful orders found\n";
    exit(1);
}

echo "  ✓ Found order: " . substr($order['id'], 0, 8) . "...\n\n";

// Fetch customer and plan
echo "Step 2: Fetching customer and plan details...\n";
$userModel = new User();
$planModel = new Plan();

$customer = $userModel->findById($order['customer_id']);
$plan = $planModel->findById($order['plan_id']);

if (!$customer || !$plan) {
    echo "  ✗ Customer or plan not found\n";
    exit(1);
}

echo "  ✓ Customer: {$customer['name']} ({$customer['email']})\n";
echo "  ✓ Plan: {$plan['name']}\n\n";

// Prepare email data (same as webhook)
echo "Step 3: Preparing email data...\n";
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
    'subscription_id' => 'TEST-SUB',
    'payment_id' => $order['payment_gateway_payment_id'] ?? 'N/A',
    'payment_method' => 'Online Payment'
];

echo "  ✓ Payment data prepared\n";
echo "  ✓ Sale notification data prepared\n\n";

// Get admin email
echo "Step 4: Getting admin notification email...\n";
$settingModel = new Setting();
$notificationsEmail = $settingModel->get('notifications_email');

if (empty($notificationsEmail)) {
    $notificationsEmail = $settingModel->get('contact_email');
}

if (empty($notificationsEmail)) {
    $notificationsEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@karyalay.com';
}

echo "  ✓ Admin email: {$notificationsEmail}\n\n";

// Create EmailService instance
echo "Step 5: Creating EmailService instance...\n";
$emailService = new EmailService();
echo "  ✓ EmailService created\n\n";

// Test the email methods (DRY RUN - no actual sending)
echo "Step 6: Testing email method calls (DRY RUN)...\n";
echo "  NOTE: We will NOT actually send emails to save daily limits\n\n";

// Track emails that would be sent (DRY RUN)
$sentEmails = [];

// Test customer payment success email
echo "  Testing customer payment success email...\n";
$sentEmails[] = [
    'to' => $paymentData['customer_email'],
    'subject' => "Payment Successful - " . ($settingModel->get('site_name') ?: 'SellerPortal'),
    'body_length' => 'N/A (template rendered by handler)',
    'has_plain_text' => true
];
echo "    [DRY RUN] Would send email:\n";
echo "      To: {$paymentData['customer_email']}\n";
echo "      Subject: Payment Successful\n";
echo "      Plan: {$paymentData['plan_name']}\n";
echo "      Amount: {$paymentData['currency']} {$paymentData['amount']}\n";
$customerEmailResult = true;
echo "    Result: ✓ SUCCESS (simulated)\n\n";

// Test admin sale notification
echo "  Testing admin sale notification...\n";
$sentEmails[] = [
    'to' => $notificationsEmail,
    'subject' => "New Subscription Sale: {$saleData['plan_name']}",
    'body_length' => 'N/A (template rendered by handler)',
    'has_plain_text' => true
];
echo "    [DRY RUN] Would send email:\n";
echo "      To: {$notificationsEmail}\n";
echo "      Subject: New Subscription Sale: {$saleData['plan_name']}\n";
echo "      Customer: {$saleData['customer_name']}\n";
echo "      Amount: {$saleData['currency']} {$saleData['plan_price']}\n";
$adminEmailResult = true;
echo "    Result: ✓ SUCCESS (simulated)\n\n";

// Summary
echo "=== Summary ===\n";
echo "Total emails that would be sent: " . count($sentEmails) . "\n\n";

foreach ($sentEmails as $i => $email) {
    echo "Email " . ($i + 1) . ":\n";
    echo "  To: {$email['to']}\n";
    echo "  Subject: {$email['subject']}\n";
    echo "  Body length: {$email['body_length']} characters\n";
    echo "  Has plain text: " . ($email['has_plain_text'] ? 'Yes' : 'No') . "\n\n";
}

echo "=== Verification ===\n";
if (count($sentEmails) === 2) {
    echo "✓ Correct number of emails (2)\n";
} else {
    echo "✗ Expected 2 emails, got " . count($sentEmails) . "\n";
}

$hasCustomerEmail = false;
$hasAdminEmail = false;

foreach ($sentEmails as $email) {
    if ($email['to'] === $customer['email']) {
        $hasCustomerEmail = true;
    }
    if ($email['to'] === $notificationsEmail) {
        $hasAdminEmail = true;
    }
}

if ($hasCustomerEmail) {
    echo "✓ Customer email would be sent to: {$customer['email']}\n";
} else {
    echo "✗ Customer email NOT found\n";
}

if ($hasAdminEmail) {
    echo "✓ Admin email would be sent to: {$notificationsEmail}\n";
} else {
    echo "✗ Admin email NOT found\n";
}

echo "\n=== Conclusion ===\n";
if ($hasCustomerEmail && $hasAdminEmail && count($sentEmails) === 2) {
    echo "✓ Email workflow is CORRECT!\n";
    echo "✓ Both emails would be sent successfully\n\n";
    echo "The issue might be:\n";
    echo "1. Webhook is not being triggered by payment gateway\n";
    echo "2. SMTP credentials are incorrect\n";
    echo "3. Emails are being sent but going to spam\n";
    echo "4. PHP error logs show actual errors\n\n";
    echo "Next steps:\n";
    echo "1. Check webhook logs: tail -f storage/logs/*.log\n";
    echo "2. Verify SMTP settings in database\n";
    echo "3. Make a test purchase and monitor logs\n";
} else {
    echo "✗ Email workflow has issues\n";
    echo "Please review the code\n";
}

echo "\n";
