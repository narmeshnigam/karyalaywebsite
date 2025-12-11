<?php
/**
 * Test Payment Email Handlers Directly
 * 
 * This script tests the payment email handlers in isolation
 * to verify they work correctly.
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "=== Testing Payment Email Handlers ===\n\n";

// Test data
$paymentData = [
    'customer_name' => 'Test Customer',
    'customer_email' => 'test@example.com',
    'plan_name' => 'Test Plan',
    'amount' => '99.00',
    'currency' => 'USD',
    'order_id' => '12345678',
    'payment_id' => 'pay_test123',
    'invoice_url' => 'http://localhost/invoice.php?id=123'
];

$saleData = [
    'customer_name' => 'Test Customer',
    'customer_email' => 'test@example.com',
    'customer_phone' => '1234567890',
    'plan_name' => 'Test Plan',
    'plan_price' => '99.00',
    'currency' => 'USD',
    'order_id' => '12345678',
    'subscription_id' => 'sub_test',
    'payment_id' => 'pay_test123',
    'payment_method' => 'Online Payment'
];

// Test 1: Direct handler instantiation
echo "Test 1: Testing PaymentSuccessEmail handler directly\n";
try {
    $handler = new \Karyalay\Services\EmailServices\PaymentSuccessEmail();
    echo "  ✓ Handler instantiated\n";
    
    // Test template rendering
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('renderTemplate');
    $method->setAccessible(true);
    $html = $method->invoke($handler, $paymentData);
    
    if (strlen($html) > 100 && strpos($html, 'Test Customer') !== false) {
        echo "  ✓ Template renders correctly\n";
        echo "  ✓ Template length: " . strlen($html) . " characters\n";
    } else {
        echo "  ✗ Template rendering issue\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTest 2: Testing NewSaleNotificationEmail handler directly\n";
try {
    $handler = new \Karyalay\Services\EmailServices\NewSaleNotificationEmail();
    echo "  ✓ Handler instantiated\n";
    
    // Test template rendering
    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('renderTemplate');
    $method->setAccessible(true);
    $html = $method->invoke($handler, $saleData);
    
    if (strlen($html) > 100 && strpos($html, 'Test Plan') !== false) {
        echo "  ✓ Template renders correctly\n";
        echo "  ✓ Template length: " . strlen($html) . " characters\n";
    } else {
        echo "  ✗ Template rendering issue\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Via EmailService facade
echo "\nTest 3: Testing via EmailService facade\n";
try {
    $emailService = new \Karyalay\Services\EmailService();
    echo "  ✓ EmailService instantiated\n";
    
    // Check methods exist
    if (method_exists($emailService, 'sendPaymentSuccessEmail')) {
        echo "  ✓ sendPaymentSuccessEmail method exists\n";
    }
    
    if (method_exists($emailService, 'sendNewSaleNotification')) {
        echo "  ✓ sendNewSaleNotification method exists\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 4: Check SMTP configuration
echo "\nTest 4: Checking SMTP configuration\n";
try {
    $settingModel = new \Karyalay\Models\Setting();
    $smtpHost = $settingModel->get('smtp_host');
    $smtpPort = $settingModel->get('smtp_port');
    $smtpUsername = $settingModel->get('smtp_username');
    $smtpFromAddress = $settingModel->get('smtp_from_address');
    
    if (!empty($smtpHost)) {
        echo "  ✓ SMTP Host: $smtpHost\n";
        echo "  ✓ SMTP Port: $smtpPort\n";
        echo "  ✓ SMTP Username: $smtpUsername\n";
        echo "  ✓ From Address: $smtpFromAddress\n";
    } else {
        echo "  ✗ SMTP not configured in database\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 5: Check notification email
echo "\nTest 5: Checking admin notification email\n";
try {
    $settingModel = new \Karyalay\Models\Setting();
    $notificationsEmail = $settingModel->get('notifications_email');
    
    if (empty($notificationsEmail)) {
        $notificationsEmail = $settingModel->get('contact_email');
    }
    
    if (empty($notificationsEmail)) {
        $notificationsEmail = $_ENV['ADMIN_EMAIL'] ?? 'NOT SET';
    }
    
    echo "  ✓ Admin email: $notificationsEmail\n";
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Summary ===\n";
echo "If all tests passed, the payment email handlers are working correctly.\n";
echo "If emails are not being received:\n";
echo "1. Check spam/junk folders\n";
echo "2. Verify webhook is being triggered by payment gateway\n";
echo "3. Check webhook logs for errors\n";
echo "4. Verify SMTP credentials are correct\n";
echo "5. Check if sender email is blacklisted\n\n";

echo "To test actual email sending:\n";
echo "  php send-payment-emails-manually.php\n\n";

